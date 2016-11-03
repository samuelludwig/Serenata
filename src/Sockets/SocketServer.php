<?php

namespace PhpIntegrator\Sockets;

use UnexpectedValueException;

use React\EventLoop\LoopInterface;

use React\Socket\Server;
use React\Socket\Connection;

/**
 * Represents a socket server that handles communication with the core.
 */
class SocketServer extends Server
{
    /**
     * @var array
     */
    protected $connectionMap;

    /**
     * @var ConnectionHandlerFactory
     */
    protected $connectionHandlerFactory;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop, ConnectionHandlerFactory $connectionHandlerFactory)
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
