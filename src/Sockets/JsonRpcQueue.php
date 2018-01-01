<?php

namespace PhpIntegrator\Sockets;

use Ds;
use UnderflowException;

/**
 * JSON RPC queue.
 */
class JsonRpcQueue
{
    /**
     * @var Ds\Queue
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
        $this->queue = new Ds\Queue();
    }

    /**
     * @param JsonRpcQueueItem $item
     */
    public function push(JsonRpcQueueItem $item): void
    {
        $this->queue->push($item);
    }

    /**
     * @throws UnderflowException
     *
     * @return JsonRpcQueueItem
     */
    public function pop(): JsonRpcQueueItem
    {
        /** @var JsonRpcQueueItem $request */
        $request = $this->queue->pop();

        if ($request->getIsCancelled()) {
            return new JsonRpcQueueItem(
                $request->getRequest(),
                $request->getJsonRpcResponseSender(),
                true
            );
        }

        return $request;
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
     *
     * @return bool
     */
    private function getIsCancelled(string $requestId): bool
    {
        return $this->cancelledIds[$requestId] ?? false;
    }
}
