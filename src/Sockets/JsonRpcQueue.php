<?php

namespace Serenata\Sockets;

use Ds;
use UnderflowException;

/**
 * JSON RPC queue.
 */
final class JsonRpcQueue
{
    /**
     * @var JsonRpcRequestPriorityDeterminerInterface
     */
    private $jsonRpcRequestPriorityDeterminer;

    /**
     * @var Ds\PriorityQueue
     */
    private $queue;

    /**
     * @var string[]
     */
    private $cancelledIds = [];

    /**
     * @param JsonRpcRequestPriorityDeterminerInterface $jsonRpcRequestPriorityDeterminer
     */
    public function __construct(JsonRpcRequestPriorityDeterminerInterface $jsonRpcRequestPriorityDeterminer)
    {
        $this->jsonRpcRequestPriorityDeterminer = $jsonRpcRequestPriorityDeterminer;

        $this->queue = new Ds\PriorityQueue();
    }

    /**
     * @param JsonRpcQueueItem $item
     * @param int|null         $priority The priority of the item, see {@see JsonRpcQueueItemPriority}. Set to null to
     *                                   determine automatically (recommended).
     */
    public function push(JsonRpcQueueItem $item, ?int $priority = null): void
    {
        $priority = $priority !== null ? $priority : $this->jsonRpcRequestPriorityDeterminer->determine(
            $item->getRequest()
        );

        $this->queue->push($item, $priority);
    }

    /**
     * @throws UnderflowException
     *
     * @return JsonRpcQueueItem
     */
    public function pop(): JsonRpcQueueItem
    {
        /** @var JsonRpcQueueItem $request */
        $requestQueueItem = $this->queue->pop();

        if ($requestQueueItem->getRequest()->getId() !== null &&
            $this->getIsCancelled($requestQueueItem->getRequest()->getId())
        ) {
            $this->pruneCancelled($requestQueueItem->getRequest()->getId());

            return new JsonRpcQueueItem(
                $requestQueueItem->getRequest(),
                $requestQueueItem->getJsonRpcMessageSender(),
                true
            );
        }

        return $requestQueueItem;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->queue->isEmpty();
    }

    /**
     * @param string $requestId
     */
    public function cancel(string $requestId): void
    {
        $this->cancelledIds[$requestId] = true;
    }

    /**
     * @param string $requestId
     */
    private function pruneCancelled(string $requestId): void
    {
        unset($this->cancelledIds[$requestId]);
    }

    /**
     * @param string $requestId
     *
     * @return bool
     */
    private function getIsCancelled(string $requestId): bool
    {
        return $this->cancelledIds[$requestId] ?? false;
    }
}
