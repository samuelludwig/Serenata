<?php

namespace PhpIntegrator\UserInterface;

use React;
use RuntimeException;
use UnexpectedValueException;

use PhpIntegrator\Sockets\SocketServer;
use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcQueueItem;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;
use PhpIntegrator\Sockets\JsonRpcRequestHandlerInterface;
use PhpIntegrator\Sockets\JsonRpcConnectionHandlerFactory;

use React\EventLoop\LoopInterface;

use React\EventLoop\Timer\Timer;

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
     * @var int
     */
    private const CYCLE_COLLECTION_FREQUENCY_SECONDS = 5;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Timer|null
     */
    private $periodicQueueProcessingTimer;

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

        $this->ensurePeriodicQueueProcessingTimerIsInstalled();
    }

    /**
     * @return void
     */
    private function ensurePeriodicQueueProcessingTimerIsInstalled(): void
    {
        if ($this->periodicQueueProcessingTimer !== null) {
            return;
        }

        $this->installPeriodicQueueProcessingTimer();
    }

    /**
     * @return void
     */
    private function installPeriodicQueueProcessingTimer(): void
    {
        $this->periodicQueueProcessingTimer = $this->loop->addPeriodicTimer(
            self::REQUEST_HANDLE_FREQUENCY_SECONDS,
            function () {
                $this->processNextQueueItem();

                if ($this->getContainer()->get('requestQueue')->isEmpty()) {
                    $this->uninstallPeriodicQueueProcessingTimer();
                }
            }
        );
    }

    /**
     * @return void
     */
    private function uninstallPeriodicQueueProcessingTimer(): void
    {
        $this->loop->cancelTimer($this->periodicQueueProcessingTimer);

        $this->periodicQueueProcessingTimer = null;
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

        $this->loop->addPeriodicTimer(
            self::CYCLE_COLLECTION_FREQUENCY_SECONDS,
            function () {
                // Still try to collect cyclic references every so often. See also Bootstrap.php for the reasoning.
                // Do *not* do this after every request handle as it puts a major strain on performance, especially
                // during project indexing. Also don't cancel this timer when the last request is handled, as during
                // normal usage, the frequency may be too high to ever trigger before it is cancelled.
                gc_collect_cycles();
            }
        );
    }
}
