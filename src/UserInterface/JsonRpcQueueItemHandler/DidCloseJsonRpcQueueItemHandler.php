<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * Handles the "textDocument/didClose" notification.
 */
final class DidCloseJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        // Stubbed for now, InitializeJsonRpcQueueItemHandler indicates we don't support this yet, but stub it to avoid
        // generating errors with some clients that try to send them anyway.

        return null; // This is a notification that doesn't expect a response.
    }
}
