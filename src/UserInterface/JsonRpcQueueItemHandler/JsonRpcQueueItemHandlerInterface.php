<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Throwable;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * Interface for commands.
 */
interface JsonRpcQueueItemHandlerInterface
{
    /**
     * Executes the command.
     *
     * @param JsonRpcQueueItem $queueItem
     *
     * @throws Throwable                 when procesing the request fails.
     * @throws InvalidArgumentsException when the request is invalid or otherwise invalid arguments were passed.
     *
     * @return JsonRpcMessageInterface|null A message (e.g. a response) or null to not send any. In the latter case
     *                                      the command should usually manually send a response to the request itself or
     *                                      schedule one to be sent at a later time (e.g. via the echoMessage request).
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface;
}
