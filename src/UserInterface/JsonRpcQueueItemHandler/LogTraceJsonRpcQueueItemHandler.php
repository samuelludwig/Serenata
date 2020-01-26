<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Handles log trace notifications.
 */
final class LogTraceJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        // This is a notification that doesn't expect a response.
        $deferred = new Deferred();
        $deferred->resolve(null);

        return $deferred->promise();
    }
}
