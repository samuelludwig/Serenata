<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $parameters = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($parameters['id'])) {
            throw new InvalidArgumentsException('"id" of request to cancel must be passed');
        }

        $this->requestQueue->cancel($parameters['id']);

        return null;
    }
}
