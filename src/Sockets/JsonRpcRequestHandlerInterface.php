<?php

namespace Serenata\Sockets;

/**
 * Interface for {@see JsonRpcRequest} handlers.
 */
interface JsonRpcRequestHandlerInterface
{
    /**
     * @param JsonRpcRequest                 $request
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     *
     * @return void
     */
    public function handle(
        JsonRpcRequest $request,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender
    ): void;
}
