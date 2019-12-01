<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * Handles log trace notifications.
 */
final class LogTraceJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        return null; // This is a notification that doesn't expect a response.
    }
}
