<?php

namespace Serenata\Sockets;

use React\Socket\ConnectionInterface;

/**
 * Factory that creates instances of {@see JsonRpcConnectionHandler}.
 */
final class JsonRpcConnectionHandlerFactory implements ConnectionHandlerFactoryInterface
{
    /**
     * @var JsonRpcMessageHandlerInterface
     */
    private $jsonRpcMessageHandler;

    /**
     * @param JsonRpcMessageHandlerInterface $jsonRpcMessageHandler
     */
    public function __construct(JsonRpcMessageHandlerInterface $jsonRpcMessageHandler)
    {
        $this->jsonRpcMessageHandler = $jsonRpcMessageHandler;
    }

    /**
     * @inheritDoc
     */
    public function create(ConnectionInterface $connection)
    {
        return new JsonRpcConnectionHandler($connection, $this->jsonRpcMessageHandler);
    }
}
