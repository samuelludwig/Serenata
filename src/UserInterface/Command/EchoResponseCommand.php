<?php

namespace Serenata\UserInterface\Command;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Special command that sends the response back (echoes) included in the request.
 *
 * This command should not be invoked from outside the server.
 */
final class EchoResponseCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['response'])) {
            throw new InvalidArgumentsException('Missing "response" in parameters for echo response request');
        }

        return $arguments['response'];
    }
}
