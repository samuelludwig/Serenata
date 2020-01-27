<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * JsonRpcQueueItemHandlerthat cancels an open request.
 */
final class CancelRequestJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var JsonRpcQueue
     */
    private $requestQueue;

    /**
     * @param JsonRpcQueue $requestQueue
     */
    public function __construct(JsonRpcQueue $requestQueue)
    {
        $this->requestQueue = $requestQueue;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams() !== null ?
            $queueItem->getRequest()->getParams() :
            [];

        if (!isset($parameters['id'])) {
            throw new InvalidArgumentsException('"id" of request to cancel must be passed');
        }

        $this->requestQueue->cancel($parameters['id']);

        // This is a notification that doesn't expect a response.
        $deferred = new Deferred();
        $deferred->resolve(null);

        return $deferred->promise();
    }
}
