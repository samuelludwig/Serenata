<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Common\Position;

use Serenata\Indexing\TextDocumentContentRegistry;
use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\NameQualificationUtilities\PositionOutOfBoundsPositionalNamespaceDeterminerException;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Highlights\DocumentHighlightsRetriever;

use Serenata\Utility\MessageType;
use Serenata\Utility\MessageLogger;
use Serenata\Utility\LogMessageParams;
use Serenata\Utility\TextDocumentItem;

/**
 * Handler that retrieves a list of known symbols for a document.
 */
final class DocumentHighlightJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var DocumentHighlightsRetriever
     */
    private $documentHighlightsRetriever;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @var MessageLogger
     */
    private $messageLogger;

    /**
     * @param DocumentHighlightsRetriever $documentHighlightsRetriever
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     * @param MessageLogger               $messageLogger
     */
    public function __construct(
        DocumentHighlightsRetriever $documentHighlightsRetriever,
        TextDocumentContentRegistry $textDocumentContentRegistry,
        MessageLogger $messageLogger
    ) {
        $this->documentHighlightsRetriever = $documentHighlightsRetriever;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
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

        try {
            $results = $this->getAll(
                $parameters['textDocument']['uri'],
                $this->textDocumentContentRegistry->get($parameters['textDocument']['uri']),
                new Position($parameters['position']['line'], $parameters['position']['character'])
            );
        } catch (FileNotFoundStorageException|PositionOutOfBoundsPositionalNamespaceDeterminerException $e) {
            $this->messageLogger->log(
                new LogMessageParams(MessageType::WARNING, $e->getMessage()),
                $queueItem->getJsonRpcMessageSender()
            );

            $results = [];
        }

        $response = new JsonRpcResponse($queueItem->getRequest()->getId(), $results);

        $deferred = new Deferred();
        $deferred->resolve($response);

        return $deferred->promise();
    }

    /**
     * @param string   $uri
     * @param string   $code
     * @param Position $position
     *
     * @return array<string,mixed>|null
     */
    public function getAll(string $uri, string $code, Position $position): ?array
    {
        return $this->documentHighlightsRetriever->retrieve(new TextDocumentItem($uri, $code), $position);
    }
}
