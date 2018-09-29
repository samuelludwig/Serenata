<?php

namespace Serenata\Indexing;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

/**
 * Interface for classes that index directories and files.
 */
interface IndexerInterface
{
    /**
     * @param string                         $uri
     * @param bool                           $useLatestState
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param JsonRpcResponse|null           $responseToSendOnCompletion
     *
     * @return bool
     */
    public function index(
        string $uri,
        bool $useLatestState,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        ?JsonRpcResponse $responseToSendOnCompletion = null
    ): bool;
}
