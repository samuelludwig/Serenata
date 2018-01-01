<?php

namespace PhpIntegrator\Sockets;

use Ds;
use UnderflowException;

/**
 * JSON RPC queue.
 */
final class JsonRpcQueue
{
    /**
     * @var Ds\PriorityQueue
     */
    private $queue;

    /**
     * @var string[]
     */
    private $cancelledIds = [];

    /**
     *
     */
    public function __construct()
    {
        $this->queue = new Ds\PriorityQueue();
    }

    /**
     * @param JsonRpcQueueItem $item
     * @param int              $priority
     */
    public function push(JsonRpcQueueItem $item, int $priority = JsonRpcQueueItemPriority::NORMAL): void
    {
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
                $requestQueueItem->getJsonRpcResponseSender(),
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
