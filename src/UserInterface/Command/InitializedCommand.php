<?php

namespace Serenata\UserInterface\Command;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that handles the "initialized" notification.
 */
final class InitializedCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        return null; // This is a notification sent by the client that doesn't need any response.
    }
}
