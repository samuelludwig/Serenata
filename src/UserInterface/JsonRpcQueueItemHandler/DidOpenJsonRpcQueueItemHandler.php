<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Handles the "textDocument/didOpen" notification.
 */
final class DidOpenJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        // Stubbed for now, InitializeJsonRpcQueueItemHandler indicates we don't support this yet, but stub it to avoid
        // generating errors with some clients that try to send them anyway.
        // This is a notification that doesn't expect a response.
        $deferred = new Deferred();
        $deferred->resolve(null);

        return $deferred->promise();
    }
}
