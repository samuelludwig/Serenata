<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Special command that sends back (echoes) the message included in the request.
 *
 * This command should not be invoked from outside the server.
 */
final class EchoMessageJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $arguments = $queueItem->getRequest()->getParams() !== null ?
            $queueItem->getRequest()->getParams() :
            [];

        if (!isset($arguments['message'])) {
            throw new InvalidArgumentsException('Missing "message" in parameters for request');
        }

        $deferred = new Deferred();
        $deferred->resolve($arguments['message']);

        return $deferred->promise();
    }
}
