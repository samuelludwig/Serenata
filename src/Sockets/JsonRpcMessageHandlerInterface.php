<?php

namespace Serenata\Sockets;

/**
 * Interface for {@see JsonRpcMessageInterface} handlers.
 */
interface JsonRpcMessageHandlerInterface
{
    /**
     * @param JsonRpcMessageInterface       $message
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    public function handle(
        JsonRpcMessageInterface $message,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void;
}
