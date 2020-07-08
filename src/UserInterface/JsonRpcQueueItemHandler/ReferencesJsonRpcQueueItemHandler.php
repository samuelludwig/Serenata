<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Handler that retrieves a list of code lenses for a document.
 */
final class ReferencesJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $response = new JsonRpcResponse($queueItem->getRequest()->getId(), null);
        $deferred = new Deferred();
        $deferred->resolve($response);

        return $deferred->promise();
    }
}
