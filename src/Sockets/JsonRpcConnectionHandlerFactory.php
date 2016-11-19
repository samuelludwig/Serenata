<?php

namespace PhpIntegrator\Sockets;

use React\Socket\Connection;

/**
 * Factory that creates instances of {@see JsonRpcConnectionHandler}.
 */
class JsonRpcConnectionHandlerFactory
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
     *
     * @return JsonRpcConnectionHandler
     */
    public function create(Connection $connection)
    {
        return new JsonRpcConnectionHandler($connection, $this->jsonRpcRequestHandler);
    }
}
