<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * JsonRpcQueueItemHandlerthat handles the "initialized" notification.
 */
final class InitializedJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        return null; // This is a notification sent by the client that doesn't need any response.
    }
}
