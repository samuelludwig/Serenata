<?php

namespace PhpIntegrator\UserInterface;

use Ds;
use React;
use Throwable;
use RuntimeException;
use UnexpectedValueException;

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

        $this->scheduleQueueProcessing();
    }

    /**
     * @return void
     */
    protected function scheduleQueueProcessing(): void
    {
        $this->loop->nextTick(function () {
            $this->processNextQueueItem();

            if (!$this->getContainer()->get('requestQueue')->isEmpty()) {
                // Ensure new requests queued by commands themselves are also handled.
                $this->scheduleQueueProcessing();
            }
        });
    }

    /**
     * @return void
     */
    protected function processNextQueueItem(): void
    {
        $this->getContainer()->get('jsonRpcQueueItemProcessor')->process(
            $this->getContainer()->get('requestQueue')->pop()
        );
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
}
