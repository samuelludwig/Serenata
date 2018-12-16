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
     * @return Command\CommandInterface
     */
    public function create(string $method): Command\CommandInterface;
}
