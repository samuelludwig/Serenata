<?php

namespace Serenata\Sockets;

use React\Socket\ConnectionInterface;

/**
 * Factory that creates instances of a class that can handle a {@see ConnectionInterface}.
 */
interface ConnectionHandlerFactoryInterface
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return object
     */
    public function create(ConnectionInterface $connection);
}
