<?php

namespace Serenata\Indexing;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

/**
 * Indexes directories and files by scheduling them to be indexed after a delay.
 *
 * If the item is already scheduled, it is rescheduled (i.e. the old index request will be dropped).
 *
 * Indexing is a relatively expensive operation. Doing it on literally every buffer change will not only take up a lot
 * of time and tax the CPU, it will also prevent any other more important requests from being handled in the meantime
 * as the server is single-threaded and single-process. Debouncing, with a reasonable timeout, provides a balance
 * between keeping up with the latest state of the source and not hogging the server.
 */
final class DebouncingIndexer implements IndexerInterface
{
    /**
     * The amount of time (in seconds) to wait before indexing a file.
     *
     * @var int
     */
    private const INDEXING_DELAY_SECONDS = 0.5;

    /**
     * @var LoopInterface
     */
    private $eventLoop;

    /**
     * @var IndexerInterface
     */
    private $delegate;

    /**
     * @var TimerInterface[]
     */
    private $uriTimerMap;

    /**
     * @param LoopInterface    $eventLoop
     * @param IndexerInterface $delegate
     */
    public function __construct(LoopInterface $eventLoop, IndexerInterface $delegate)
    {
        $this->eventLoop = $eventLoop;
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function index(
        string $uri,
        bool $useLatestState,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        ?JsonRpcResponse $responseToSendOnCompletion = null
    ): bool {
        if (isset($this->uriTimerMap[$uri])) {
            $this->eventLoop->cancelTimer($this->uriTimerMap[$uri]);
        }

        $callback = function (/*TimerInterface $timer*/) use ($uri, $useLatestState, $jsonRpcResponseSender, $responseToSendOnCompletion) {
            $this->delegate->index($uri, $useLatestState, $jsonRpcResponseSender, $responseToSendOnCompletion);

            unset($this->uriTimerMap[$uri]);
        };

        $this->uriTimerMap[$uri] = $this->eventLoop->addTimer(self::INDEXING_DELAY_SECONDS, $callback);
    }
}
