<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

/**
 * Special command that sends progress information related to reindex events.
 *
 * This command should not be invoked from outside the server.
 */
class ReindexProgressCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        return new JsonRpcResponse(null, array_merge($arguments, [
            'type' => 'reindexProgressInformation'
        ]));
    }
}
