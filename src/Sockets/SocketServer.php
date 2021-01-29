<?php

namespace Serenata\Sockets;

use RuntimeException;
use SplObjectStorage;

use React\EventLoop\LoopInterface;

use React\Socket\Server;
use React\Socket\Connection;

/**
 * Represents a socket server that handles communication with the core.
 *
 * This class simply requests a configured factory to create a handler for each new connection and does not handle any
 * communication itself.
 */
final class SocketServer
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var SplObjectStorage<Connection,object>
     */
    private $connectionMap;

    /**
     * @var ConnectionHandlerFactoryInterface
     */
    private $connectionHandlerFactory;

    /**
     * @param string                            $uri
     * @param LoopInterface                     $loop
     * @param ConnectionHandlerFactoryInterface $connectionHandlerFactory
     *
     * @throws RuntimeException when setting up the server is impossible (e.g. the socket is already in use).
     */
    public function __construct(
        string $uri,
        LoopInterface $loop,
        ConnectionHandlerFactoryInterface $connectionHandlerFactory
    ) {
        /** @var SplObjectStorage<Connection,object> $storage */
        $storage = new SplObjectStorage();
        $this->connectionMap = $storage;

        $this->server = new Server($uri, $loop);
        $this->connectionHandlerFactory = $connectionHandlerFactory;

        $this->server->on('connection', function (Connection $connection): void {
             $this->onConnectionEstablished($connection);
        });
    }

    /**
     * @param Connection $connection
     *
     * @return void
     */
    private function onConnectionEstablished(Connection $connection): void
    {
        $handler = $this->connectionHandlerFactory->create($connection);

        $this->connectionMap->attach($connection, $handler);

        $connection->on('close', function () use ($connection): void {
            $this->onConnectionClosed($connection);
        });
    }

    /**
     * @param Connection $connection
     *
     * @return void
     */
    private function onConnectionClosed(Connection $connection): void
    {
        $this->connectionMap->detach($connection);
    }
}
