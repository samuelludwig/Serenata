<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\IndexerInterface;
use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

/**
 * Handles the "textDocument/didChange" notification.
 */
final class DidChangeJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @param IndexerInterface            $indexer
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     */
    public function __construct(IndexerInterface $indexer, TextDocumentContentRegistry $textDocumentContentRegistry)
    {
        $this->indexer = $indexer;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams();

        if ($parameters === null || $parameters === []) {
            throw new InvalidArgumentsException('Missing parameters for didChangeWatchedFiles request');
        }

        $promise = $this->handle(
            $parameters['textDocument']['uri'],
            $parameters['contentChanges'][count($parameters['contentChanges']) - 1]['text'],
            $queueItem->getJsonRpcMessageSender()
        )->then(function () {
            // This is a notification that doesn't expect a response.
            return null;
        });

        assert($promise instanceof ExtendedPromiseInterface);

        return $promise;
    }

    /**
     * @param string                        $uri
     * @param string                        $contents
     * @param JsonRpcMessageSenderInterface $sender
     *
     * @return ExtendedPromiseInterface ExtendedPromiseInterface<bool>
     */
    public function handle(
        string $uri,
        string $contents,
        JsonRpcMessageSenderInterface $sender
    ): ExtendedPromiseInterface {
        $this->textDocumentContentRegistry->update($uri, $contents);

        return $this->indexer->index($uri, true, $sender);
    }
}
