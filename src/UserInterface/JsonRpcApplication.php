<?php

namespace PhpIntegrator\UserInterface;

use Ds;
use React;
use Throwable;
use RuntimeException;
use UnexpectedValueException;

use PhpIntegrator\Indexing\IncorrectDatabaseVersionException;

use PhpIntegrator\Sockets\JsonRpcError;
use PhpIntegrator\Sockets\SocketServer;
use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;
use PhpIntegrator\Sockets\JsonRpcErrorCode;
use PhpIntegrator\Sockets\RequestParsingException;
use PhpIntegrator\Sockets\JsonRpcRequestHandlerInterface;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;
use PhpIntegrator\Sockets\JsonRpcConnectionHandlerFactory;

use React\EventLoop\LoopInterface;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Application extension that can handle JSON-RPC requests.
 */
class JsonRpcApplication extends AbstractApplication implements JsonRpcRequestHandlerInterface
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * A stream that is used to read and write STDIN data from.
     *
     * As there is no actual STDIN when working with sockets, this temporary stream is used to transparently replace
     * it with another stream.
     *
     * @var resource|null
     */
    private $stdinStream;

    /**
     * @inheritDoc
     */
    public function run()
    {
        $options = getopt('p:', [
            'port:'
        ]);

        $requestHandlingPort = $this->getRequestHandlingPortFromOptions($options);

        $this->stdinStream = fopen('php://memory', 'w+');

        $this->loop = React\EventLoop\Factory::create();

        try {
            $this->setupRequestHandlingSocketServer($this->loop, $requestHandlingPort);
        } catch (RuntimeException $e) {
            fwrite(STDERR, 'Socket already in use!');
            fclose($this->stdinStream);
            return 2;
        }

        echo "Starting socket server on port {$requestHandlingPort}...\n";

        $this->instantiateRequiredServices($this->getContainer());

        $this->loop->run();

        fclose($this->stdinStream);

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function handle(JsonRpcRequest $request, JsonRpcResponseSenderInterface $jsonRpcResponseSender): void
    {
        $this->getContainer()->get('requestQueue')->push(new JsonRpcQueueItem($request, $jsonRpcResponseSender));

        $this->loop->nextTick(function () {
            $this->processQueueItem();
        });
    }

    /**
     * @param array $options
     *
     * @throws UnexpectedValueException
     *
     * @return int
     */
    protected function getRequestHandlingPortFromOptions(array $options): int
    {
        if (isset($options['p'])) {
            return (int) $options['p'];
        } elseif (isset($options['port'])) {
            return (int) $options['port'];
        }

        throw new UnexpectedValueException('A socket port for handling requests must be specified');
    }

    /**
     * @param React\EventLoop\LoopInterface $loop
     * @param int                           $port
     *
     * @throws RuntimeException
     *
     * @return void
     */
    protected function setupRequestHandlingSocketServer(React\EventLoop\LoopInterface $loop, int $port): void
    {
        $connectionHandlerFactory = new JsonRpcConnectionHandlerFactory($this);

        $requestHandlingSocketServer = new SocketServer($port, $loop, $connectionHandlerFactory);
    }

    /**
     * @return void
     */
    protected function processQueueItem(): void
    {
        /** @var JsonRpcQueueItem $queueItem */
        $queueItem = $this->getContainer()->get('requestQueue')->pop();

        $response = $this->processRequest($queueItem->getRequest());

        $queueItem->getJsonRpcResponseSender()->send($response);
    }

    /**
     * @param JsonRpcRequest $request
     *
     * @return JsonRpcResponse
     */
    protected function processRequest(JsonRpcRequest $request): JsonRpcResponse
    {
        $error = null;
        $result = null;

        try {
            $result = $this->handleRequest($request);
        } catch (RequestParsingException $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $e->getMessage());
        } catch (Command\InvalidArgumentsException $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $e->getMessage());
        } catch (IncorrectDatabaseVersionException $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::DATABASE_VERSION_MISMATCH, $e->getMessage());
        } catch (\RuntimeException $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::GENERIC_RUNTIME_ERROR, $e->getMessage());
        } catch (\Throwable $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::FATAL_SERVER_ERROR, $e->getMessage(), [
                'line'      => $e->getLine(),
                'file'      => $e->getFile(),
                'backtrace' => $this->getCompleteBacktraceFromThrowable($e)
            ]);
        }

        if ($result instanceof JsonRpcResponse) {
            return $result;
        }

        return new JsonRpcResponse($request->getId(), $result, $error);
    }

    /**
     * @param Throwable $throwable
     *
     * @return string
     */
    protected function getCompleteBacktraceFromThrowable(Throwable $throwable): string
    {
        $counter = 1;

        $reducer = function (string $carry, Throwable $item) use (&$counter): string {
            if (!empty($carry)) {
                $carry .= "\n \n";
            }

            $carry .= "→ Message {$counter}\n";
            $carry .= $item->getMessage() . "\n \n";

            $carry .= "→ Location {$counter}\n";
            $carry .= $item->getFile() . ':' . $item->getLine() . "\n \n";

            $carry .= "→ Backtrace {$counter}\n";
            $carry .= $item->getTraceAsString();

            ++$counter;

            return $carry;
        };

        return $this->getThrowableVector($throwable)->reduce($reducer, '');
    }

    /**
     * @param Throwable $throwable
     *
     * @return Ds\Vector
     */
    protected function getThrowableVector(Throwable $throwable): Ds\Vector
    {
        $vector = new Ds\Vector();

        $item = $throwable;

        while ($item) {
            $vector[] = $item;

            $item = $item->getPrevious();
        }

        return $vector;
    }

    /**
     * @param JsonRpcRequest $request
     *
     * @return mixed
     */
    protected function handleRequest(JsonRpcRequest $request)
    {
        $params = $request->getParams();

        if (isset($params['stdinData'])) {
            ftruncate($this->stdinStream, 0);
            fwrite($this->stdinStream, $params['stdinData']);
            rewind($this->stdinStream);
        }

        if (isset($params['database'])) {
            $this->setDatabaseFile($params['database']);
        }

        return $this->getCommandByMethod($request->getMethod())->execute($request);
    }

    /**
     * @param string $method
     *
     * @return Command\CommandInterface
     */
    protected function getCommandByMethod(string $method): Command\CommandInterface
    {
        try {
            return $this->getContainer()->get($method . 'Command');
        } catch (ServiceNotFoundException $e) {
            throw new RequestParsingException('Method "' . $method . '" was not found');
        }

        return null; // Never reached.
    }

    /**
     * @inheritDoc
     */
    public function getStdinStream()
    {
        return $this->stdinStream;
    }
}
