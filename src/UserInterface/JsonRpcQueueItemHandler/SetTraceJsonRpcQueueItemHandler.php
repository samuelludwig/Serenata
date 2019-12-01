<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * * Handles set trace notifications.
 */
final class SetTraceJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        return null; // This is a notification that doesn't expect a response.
    }
}
