<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['response'])) {
            throw new InvalidArgumentsException('Missing response in parameters for echo response request');
        }

        return $arguments['response'];
    }
}
