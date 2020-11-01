<?php

namespace Serenata\Indexing;

use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcMessageSenderInterface;

/**
 * Interface for classes that index directories and files.
 */
interface IndexerInterface
{
    /**
     * @param string                         $uri
     * @param bool                           $useLatestState
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     *
     * @return ExtendedPromiseInterface ExtendedPromiseInterface<bool>
     */
    public function index(
        string $uri,
        bool $useLatestState,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): ExtendedPromiseInterface;
}
