<?php

namespace Serenata\UserInterface\Command;

use Throwable;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Interface for commands.
 */
interface CommandInterface
{
    /**
     * Executes the command.
     *
     * @param JsonRpcRequest $request
     *
     * @throws Throwable                 when procesing the request fails.
     * @throws InvalidArgumentsException when the request is invalid or otherwise invalid arguments were passed.
     *
     * @return JsonRpcResponse|null Either a response or null to not send any response (in that case the command MUST
     *                              manually send a response to the request if appropriate, or queue a new request that
     *                              will do so).
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse;
}
