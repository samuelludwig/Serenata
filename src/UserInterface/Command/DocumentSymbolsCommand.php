<?php

namespace Serenata\UserInterface\Command;

use Serenata\Indexing\StorageInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Symbols\SymbolInformation;
use Serenata\Symbols\DocumentSymbolRetriever;

/**
 * Command that retrieves a list of known symbols for a document.
 */
final class DocumentSymbolsCommand extends AbstractCommand
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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
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
        $file = $this->storage->getFileByPath($uri);

        return $this->documentSymbolRetriever->retrieve($file);
    }
}
