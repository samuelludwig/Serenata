<?php

namespace Serenata\Sockets;

/**
 * Interface for classes that can send {@see JsonRpcMessageInterface} objects over a stream, socket, file, ....
 */
interface JsonRpcMessageSenderInterface
{
    /**
     * @param JsonRpcMessageInterface $response
     */
    public function send(JsonRpcMessageInterface $response): void;
}
