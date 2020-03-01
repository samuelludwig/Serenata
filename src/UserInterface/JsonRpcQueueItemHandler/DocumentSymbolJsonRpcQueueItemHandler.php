<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\NameQualificationUtilities\PositionOutOfBoundsPositionalNamespaceDeterminerException;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Symbols\SymbolInformation;
use Serenata\Symbols\DocumentSymbolRetriever;

use Serenata\Utility\MessageType;
use Serenata\Utility\MessageLogger;
use Serenata\Utility\LogMessageParams;

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
     * @var MessageLogger
     */
    private $messageLogger;

    /**
     * @param StorageInterface        $storage
     * @param DocumentSymbolRetriever $documentSymbolRetriever
     * @param MessageLogger           $messageLogger
     */
    public function __construct(
        StorageInterface $storage,
        DocumentSymbolRetriever $documentSymbolRetriever,
        MessageLogger $messageLogger
    ) {
        $this->storage = $storage;
        $this->documentSymbolRetriever = $documentSymbolRetriever;
        $this->messageLogger = $messageLogger;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams() !== null ?
            $queueItem->getRequest()->getParams() :
            [];

        $results = [];

        try {
            $results = $this->getAll($parameters['textDocument']['uri']);
        } catch (FileNotFoundStorageException|PositionOutOfBoundsPositionalNamespaceDeterminerException $e) {
            $this->messageLogger->log(
                new LogMessageParams(MessageType::WARNING, $e->getMessage()),
                $queueItem->getJsonRpcMessageSender()
            );

            $result = null;
        }

        $response = new JsonRpcResponse($queueItem->getRequest()->getId(), $results);

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
        return $this->documentSymbolRetriever->retrieve($this->storage->getFileByUri($uri));
    }
}
