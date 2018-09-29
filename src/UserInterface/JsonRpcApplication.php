<?php

namespace Serenata\UserInterface;

use React;
use Closure;
use RuntimeException;
use UnexpectedValueException;

use React\EventLoop\TimerInterface;

use Serenata\Sockets\SocketServer;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcRequestHandlerInterface;
use Serenata\Sockets\JsonRpcResponseSenderInterface;
use Serenata\Sockets\JsonRpcConnectionHandlerFactory;

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
    private const REQUEST_HANDLE_FREQUENCY_SECONDS = 0;

    /**
     * @var int
     */
    private const CYCLE_COLLECTION_FREQUENCY_SECONDS = 5;
    /**
     * @var TimerInterface|null
     */
    private $periodicQueueProcessingTimer;

    /**
     * @inheritDoc
     */
    public function run()
    {
        $application = (new Application('PHP Integrator Core'))
            ->register('start')
                ->addOption('uri', 'u', InputOption::VALUE_OPTIONAL, 'The URI to run on', null)
                ->setCode(Closure::fromCallable([$this, 'runEventLoop']))
            ->getApplication();

        $application->setAutoExit(false);
        $application->setDefaultCommand('start', true);

        return $application->run();
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
     * phpcs:disable -- runEventLoop is called with array syntax above and not an unused method.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function runEventLoop(InputInterface $input, OutputInterface $output): int
    {
        // phpcs:enable
        $uri = $input->getOption('uri');

        if ($uri === null) {
            throw new UnexpectedValueException('A URI for handling requests must be specified');
        }

        $loop = $this->getContainer()->get('eventLoop');

        try {
            $this->setupRequestHandlingSocketServer($loop, $uri);
        } catch (RuntimeException $e) {
            $output->writeln("<error>Could not bind to socket on URI {$uri}</>");
            return 2;
        }

        $output->writeln("<info>Starting server bound to socket on URI {$uri}...</>");

        $this->instantiateRequiredServices($this->getContainer());

        $loop->run();

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
        $this->periodicQueueProcessingTimer = $this->getContainer()->get('eventLoop')->addPeriodicTimer(
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
        if ($this->periodicQueueProcessingTimer) {
            $this->getContainer()->get('eventLoop')->cancelTimer($this->periodicQueueProcessingTimer);

            $this->periodicQueueProcessingTimer = null;
        }
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

        $this->getContainer()->get('eventLoop')->addPeriodicTimer(
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
