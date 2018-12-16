<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Indexing\StorageInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

use Serenata\Symbols\SymbolInformation;
use Serenata\Symbols\DocumentSymbolRetriever;

/**
 * JsonRpcQueueItemHandlerthat retrieves a list of known symbols for a document.
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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $parameters = $queueItem->getRequest()->getParams() ?: [];

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->getAll($parameters['textDocument']['uri'])
        );
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
