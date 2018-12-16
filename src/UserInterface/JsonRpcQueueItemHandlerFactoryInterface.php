<?php

namespace Serenata\UserInterface;

/**
 * Generates an appropriate handler for a {@see JsonRpcQueueItem}
 */
interface JsonRpcQueueItemHandlerFactoryInterface
{
    /**
     * @param string $method
     *
     * @return JsonRpcQueueItemHandler\JsonRpcQueueItemHandlerInterface
     */
    public function create(string $method): JsonRpcQueueItemHandler\JsonRpcQueueItemHandlerInterface;
}
