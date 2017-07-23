<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Sockets\JsonRpcRequest;

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
     * @return mixed
     */
    public function execute(JsonRpcRequest $request);
}
