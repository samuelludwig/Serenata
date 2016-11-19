<?php

namespace PhpIntegrator\Sockets;

use React\EventLoop\LoopInterface;

use React\Socket\Server;
use React\Socket\Connection;

/**
 * Represents a socket server that handles communication with the core.
 *
 * This class simply requests a configured factory to create a handler for each new connection and does not handle any
 * communication itself.
 */
class SocketServer extends Server
{
    /**
     * @var array
     */
    protected $connectionMap;

    /**
     * @var ConnectionHandlerFactoryInterface
     */
    protected $connectionHandlerFactory;

    /**
     * @param LoopInterface                     $loop
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory
     */
    public function __construct(LoopInterface $loop, ConnectionHandlerFactoryInterface $connectionHandlerFactory)
    {
        parent::__construct($loop);

        $this->connectionHandlerFactory = $connectionHandlerFactory;

        $this->on('connection', [$this, 'onConnectionEstablished']);
    }

    /**
     * @param Connection $connection
     */
    protected function onConnectionEstablished(Connection $connection)
    {
        $key = $this->getKeyForConnection($connection);

        $this->connectionMap[$key] = $this->connectionHandlerFactory->create($connection);

        $connection->on('close', [$this, 'onConnectionClosed']);
    }

    /**
     * @param Connection $connection
     */
    protected function onConnectionClosed(Connection $connection)
    {
        $key = $this->getKeyForConnection($connection);

        unset($this->connectionMap[$key]);
    }

    /**
     * @param Connection $connection
     *
     * @return string
     */
    protected function getKeyForConnection(Connection $connection)
    {
        return spl_object_hash($connection);
    }
}
