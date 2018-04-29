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

use Symfony\Component\Console\Application;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;

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
        $consoleApplication = new Application('PHP Integrator Core');
        $consoleApplication
            ->register('start')
                ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'The port to run on', null)
                ->setCode(\Closure::fromCallable([$this, 'runEventLoop']))
            ->getApplication()
            ->setDefaultCommand('start', true)
            ->run();
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function runEventLoop(InputInterface $input, OutputInterface $output): int
    {
        $requestHandlingPort = $input->getOption('port');

        if ($requestHandlingPort === null) {
            throw new UnexpectedValueException('A socket port for handling requests must be specified');
        }

        $this->loop = React\EventLoop\Factory::create();

        $uri = $requestHandlingPort;

        try {
            $this->setupRequestHandlingSocketServer($this->loop, $uri);
        } catch (RuntimeException $e) {
            $output->writeln("<error>Could not bind to socket on URI {$uri}</>");
            return 2;
        }

        $output->writeln("<info>Starting server bound to socket on URI {$uri}...</>");

        $this->instantiateRequiredServices($this->getContainer());

        $this->loop->run();

        return 0;
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
     * @param React\EventLoop\LoopInterface $loop
     * @param string                        $uri
     *
     * @throws RuntimeException
     *
     * @return void
     */
    private function setupRequestHandlingSocketServer(React\EventLoop\LoopInterface $loop, string $uri): void
    {
        $connectionHandlerFactory = new JsonRpcConnectionHandlerFactory($this);

        $requestHandlingSocketServer = new SocketServer($uri, $loop, $connectionHandlerFactory);

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
