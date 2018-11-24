<?php

namespace Serenata\UserInterface\Command;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * Special command that sends back (echoes) the message included in the request.
 *
 * This command should not be invoked from outside the server.
 */
final class EchoMessageCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['message'])) {
            throw new InvalidArgumentsException('Missing "message" in parameters for request');
        }

        return $arguments['message'];
    }
}
