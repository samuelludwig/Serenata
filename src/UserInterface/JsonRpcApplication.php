<?php

namespace PhpIntegrator\UserInterface;

use Ds;
use React;
use Throwable;
use RuntimeException;
use UnexpectedValueException;

use PhpIntegrator\Sockets\SocketServer;
use PhpIntegrator\Sockets\JsonRpcError;
use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcErrorCode;
use PhpIntegrator\Sockets\JsonRpcQueueItem;
use PhpIntegrator\Sockets\RequestParsingException;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;
use PhpIntegrator\Sockets\JsonRpcRequestHandlerInterface;
use PhpIntegrator\Sockets\JsonRpcConnectionHandlerFactory;

use React\EventLoop\LoopInterface;

use React\EventLoop\Timer\Timer;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Application extension that can handle JSON-RPC requests.
 */
final class JsonRpcApplication extends AbstractApplication implements JsonRpcRequestHandlerInterface
{
    /**
     * @var float
     */
    private const REQUEST_HANDLE_FREQUENCY_SECONDS = 0.00001;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Timer
     */
    private $periodicTimer;

    /**
     * @inheritDoc
     */
    public function run()
    {
        $options = getopt('p:', [
            'port:'
        ]);

        $requestHandlingPort = $this->getRequestHandlingPortFromOptions($options);

        $this->loop = React\EventLoop\Factory::create();

        try {
            $this->setupRequestHandlingSocketServer($this->loop, $requestHandlingPort);
        } catch (RuntimeException $e) {
            fwrite(STDERR, 'Socket already in use!');
            return 2;
        }

        echo "Starting socket server on port {$requestHandlingPort}...\n";

        $this->instantiateRequiredServices($this->getContainer());

        $this->loop->run();

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function handle(JsonRpcRequest $request, JsonRpcResponseSenderInterface $jsonRpcResponseSender): void
    {
        $this->getContainer()->get('requestQueue')->push(new JsonRpcQueueItem($request, $jsonRpcResponseSender));

        $this->ensurePeriodicTimerIsInstalled();
    }

    /**
     * @return void
     */
    private function ensurePeriodicTimerIsInstalled(): void
    {
        if ($this->periodicTimer !== null) {
            return;
        }

        $this->installPeriodicTimer();
    }


    /**
     * @return void
     */
    private function installPeriodicTimer(): void
    {
        $this->periodicTimer = $this->loop->addPeriodicTimer(self::REQUEST_HANDLE_FREQUENCY_SECONDS, function () {
            $this->processNextQueueItem();

            if ($this->getContainer()->get('requestQueue')->isEmpty()) {
                $this->uninstallPeriodicTimer();
            }
        });
    }

    /**
     * @return void
     */
    private function uninstallPeriodicTimer(): void
    {
        $this->loop->cancelTimer($this->periodicTimer);
        $this->periodicTimer = null;
    }





    /**
     * @return void
     */
    private function processNextQueueItem(): void
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
    private function getRequestHandlingPortFromOptions(array $options): int
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
    private function setupRequestHandlingSocketServer(React\EventLoop\LoopInterface $loop, int $port): void
    {
        $connectionHandlerFactory = new JsonRpcConnectionHandlerFactory($this);

        $requestHandlingSocketServer = new SocketServer($port, $loop, $connectionHandlerFactory);
    }
}
