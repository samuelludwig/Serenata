<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;

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
     * @return JsonRpcResponse|mixed
     */
    public function execute(JsonRpcRequest $request, JsonRpcResponseSenderInterface $jsonRpcResponseSender);
}
