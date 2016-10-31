<?php

namespace PhpIntegrator\Sockets;

use React\Socket\Connection;

/**
 * Represents a socket server that handles communication with the core.
 */
class ConnectionHandlerFactory
{
    /**
     * @var JsonRpcRequestHandlerInterface
     */
    protected $jsonRpcRequestHandler;

    /**
     * @param JsonRpcRequestHandlerInterface $jsonRpcRequestHandler
     */
    public function __construct(JsonRpcRequestHandlerInterface $jsonRpcRequestHandler)
    {
        $this->jsonRpcRequestHandler = $jsonRpcRequestHandler;
    }

    /**
     * @param Connection $connection
     */
    public function create(Connection $connection)
    {
        return new ConnectionHandler($connection, $this->jsonRpcRequestHandler);
    }
}
