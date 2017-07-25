<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponse;

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
    public function execute(JsonRpcRequest $request)
    {
        $arguments = $request->getParams() ?: [];

        return new JsonRpcResponse(null, array_merge($arguments, [
            'type' => 'reindexProgressInformation'
        ]));
    }
}
