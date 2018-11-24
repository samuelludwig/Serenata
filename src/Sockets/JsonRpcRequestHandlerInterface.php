<?php

namespace Serenata\Sockets;

/**
 * Interface for {@see JsonRpcRequest} handlers.
 */
interface JsonRpcRequestHandlerInterface
{
    /**
     * @param JsonRpcRequest                 $request
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     *
     * @return void
     */
    public function handle(
        JsonRpcRequest $request,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void;
}
