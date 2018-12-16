<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Indexing\IndexerInterface;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

/**
 * Indexes a URI directly.
 *
 * This command bypasses the ordenary channels (such as didChange and didChangeWatchedFiles) and thus circumvents
 * things such as debouncing.
 *
 * Having a separate command also allows assigning a separate priority to the handling of this event and other commands
 * that indirectly also index URI's.
 *
 * This command should not be invoked from outside the server.
 */
final class IndexJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @param IndexerInterface $indexer
     */
    public function __construct(IndexerInterface $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $parameters = $queueItem->getRequest()->getParams();

        if (!$parameters) {
            throw new InvalidArgumentsException('Missing parameters for index request');
        }

        $this->handle($parameters['textDocument']['uri'], $queueItem->getJsonRpcMessageSender());

        return null; // This is a notification that doesn't expect a response.
    }

    /**
     * @param string                         $uri
     * @param JsonRpcMessageSenderInterface $sender
     */
    public function handle(string $uri, JsonRpcMessageSenderInterface $sender): void
    {
        $this->indexer->index($uri, true, $sender);
    }
}
