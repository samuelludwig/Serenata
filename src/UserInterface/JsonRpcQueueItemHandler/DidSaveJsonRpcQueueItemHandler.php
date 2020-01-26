<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\IndexerInterface;
use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

/**
 * Handles the "textDocument/didSave" notification.
 *
 * Sending this request is likely unnecessary as "textDocument/didChange" already keeps track of changes and
 * "workspace/didChangeWatchedFiles" keeps track of saves as well. Still, it may be useful for clients that do not
 * support sending changes yet or simply for clients that allow disabling change requests so that users can only let
 * the server be notified when a document is saved (i.e. to only index changes on save instead of on change).
 *
 * Also, receiving all requests imposes little additional overhead for the server as the UnmodifiedFileSkippingIndexer
 * already ensures the same content is indexed only once.
 */
final class DidSaveJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
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

        if (!$parameters) {
            throw new InvalidArgumentsException('Missing parameters for didChangeWatchedFiles request');
        }

        $this->handle($parameters['textDocument']['uri'], $parameters['text'], $queueItem->getJsonRpcMessageSender());

        // This is a notification that doesn't expect a response.
        $deferred = new Deferred();
        $deferred->resolve(null);

        return $deferred->promise();
    }

    /**
     * @param string                        $uri
     * @param string|null                   $contents
     * @param JsonRpcMessageSenderInterface $sender
     */
    public function handle(string $uri, ?string $contents, JsonRpcMessageSenderInterface $sender): void
    {
        if ($contents !== null) {
            $this->textDocumentContentRegistry->update($uri, $contents);
        } else {
            // Force refetching from the file, since it now has the latest state.
            $this->textDocumentContentRegistry->clear($uri);
        }

        $this->indexer->index($uri, true, $sender);
    }
}
