<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\StorageInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Symbols\SymbolInformation;
use Serenata\Symbols\DocumentSymbolRetriever;

/**
 * Handler that retrieves a list of known symbols for a document.
 */
final class DocumentSymbolJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DocumentSymbolRetriever
     */
    private $documentSymbolRetriever;

    /**
     * @param StorageInterface        $storage
     * @param DocumentSymbolRetriever $documentSymbolRetriever
     */
    public function __construct(StorageInterface $storage, DocumentSymbolRetriever $documentSymbolRetriever)
    {
        $this->storage = $storage;
        $this->documentSymbolRetriever = $documentSymbolRetriever;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams() ?: [];

        $response = new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->getAll($parameters['textDocument']['uri'])
        );

        $deferred = new Deferred();
        $deferred->resolve($response);

        return $deferred->promise();
    }

    /**
     * @param string $uri
     *
     * @return SymbolInformation[]|null
     */
    public function getAll(string $uri): ?array
    {
        $file = $this->storage->getFileByUri($uri);

        return $this->documentSymbolRetriever->retrieve($file);
    }
}
