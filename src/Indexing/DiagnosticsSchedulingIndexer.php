<?php

namespace Serenata\Indexing;

use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

/**
 * Schedules a request for sending diagnostics after file or folder indexing completes.
 */
final class DiagnosticsSchedulingIndexer implements IndexerInterface
{
    /**
     * @var IndexerInterface
     */
    private $delegate;

    /**
     * @var JsonRpcQueue
     */
    private $queue;

    /**
     * @param IndexerInterface $delegate
     * @param JsonRpcQueue     $queue
     */
    public function __construct(IndexerInterface $delegate, JsonRpcQueue $queue)
    {
        $this->delegate = $delegate;
        $this->queue = $queue;
    }

    /**
     * @inheritDoc
     */
    public function index(
        string $uri,
        bool $useLatestState,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): ExtendedPromiseInterface {
        $promise = $this->delegate->index($uri, $useLatestState, $jsonRpcMessageSender)->then(
            function (bool $response) use ($uri, $jsonRpcMessageSender): bool {
                if (!$response || !is_file($uri)) {
                    return $response;
                }

                $request = new JsonRpcRequest(null, 'serenata/internal/diagnostics', [
                    'uri'  => $uri,
                ]);

                $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));

                return true;
            }
        );

        assert($promise instanceof ExtendedPromiseInterface);

        return $promise;
    }
}
