<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

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
     * @throws InvalidArgumentsException
     *
     * @return JsonRpcResponse|null Either a response or null to not send any response (in that case the command MUST
     *                              manually send a response to the request if appropriate, or queue a new request that
     *                              will do so).
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse;
}
