<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Indexing\IndexerInterface;
use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;
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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $parameters = $queueItem->getRequest()->getParams();

        if (!$parameters) {
            throw new InvalidArgumentsException('Missing parameters for didChangeWatchedFiles request');
        }

        $this->handle(
            $parameters['textDocument']['uri'],
            $parameters['contentChanges'][count($parameters['contentChanges']) - 1]['text'],
            $queueItem->getJsonRpcMessageSender()
        );

        return null; // This is a notification that doesn't expect a response.
    }

    /**
     * @param string                        $uri
     * @param string                        $contents
     * @param JsonRpcMessageSenderInterface $sender
     */
    public function handle(string $uri, string $contents, JsonRpcMessageSenderInterface $sender): void
    {
        $this->textDocumentContentRegistry->update($uri, $contents);

        $this->indexer->index($uri, true, $sender);
    }
}
