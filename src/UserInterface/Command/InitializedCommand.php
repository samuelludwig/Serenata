<?php

namespace Serenata\UserInterface\Command;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * Command that handles the "initialized" notification.
 */
final class InitializedCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        return null; // This is a notification sent by the client that doesn't need any response.
    }
}
