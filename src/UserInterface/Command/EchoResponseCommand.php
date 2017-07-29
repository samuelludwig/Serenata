<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;

/**
 * Special command that sends the response back (echoes) included in the request.
 *
 * This command should not be invoked from outside the server.
 */
class EchoResponseCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcRequest $request, JsonRpcResponseSenderInterface $jsonRpcResponseSender)
    {
        $arguments = $request->getParams() ?: [];

        if (!isset($arguments['response'])) {
            throw new InvalidArgumentsException('Missing response in parameters for echo response request');
        }

        return $arguments['response'];
    }
}
