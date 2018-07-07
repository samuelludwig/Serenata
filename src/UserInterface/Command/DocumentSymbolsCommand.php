<?php

namespace Serenata\UserInterface\Command;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Symbols\SymbolInformation;
use Serenata\Symbols\DocumentSymbolRetriever;

use Serenata\Indexing\StorageInterface;

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
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A --file must be supplied!');
        }

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $this->getAll($arguments['file']));
    }

    /**
     * @param string $filePath
     *
     * @return SymbolInformation[]|null
     */
    public function getAll(string $filePath): ?array
    {
        $file = $this->storage->getFileByPath($filePath);

        return $this->documentSymbolRetriever->retrieve($file);
    }
}
