<?php

namespace Serenata\UserInterface\Command;

use UnexpectedValueException;

use Serenata\Indexing\Indexer;
use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * Handles the "textDocument/didChange" notification.
 */
final class DidChangeCommand extends AbstractCommand
{
    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @param ActiveWorkspaceManager      $activeWorkspaceManager
     * @param Indexer                     $indexer
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     */
    public function __construct(
        ActiveWorkspaceManager $activeWorkspaceManager,
        Indexer $indexer,
        TextDocumentContentRegistry $textDocumentContentRegistry
    ) {
        $this->activeWorkspaceManager = $activeWorkspaceManager;
        $this->indexer = $indexer;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $parameters = $queueItem->getRequest()->getParams();

        if (!$parameters) {
            throw new InvalidArgumentsException('Missing parameters for didChangeWatchedFiles request');
        }

        $this->handle(
            $parameters['textDocument']['uri'],
            $parameters['contentChanges'][count($parameters['contentChanges']) - 1]['text'],
            $queueItem->getJsonRpcResponseSender()
        );

        return null; // This is a notification that doesn't expect a response.
    }

    /**
     * @param string                         $uri
     * @param string                         $contents
     * @param JsonRpcResponseSenderInterface $sender
     */
    public function handle(string $uri, string $contents, JsonRpcResponseSenderInterface $sender): void
    {
        $workspace = $this->activeWorkspaceManager->getActiveWorkspace();

        if (!$workspace) {
            throw new UnexpectedValueException(
                'Cannot handle file change event when there is no active workspace, did you send an initialize ' .
                'request first?'
            );
        }

        $this->textDocumentContentRegistry->update($uri, $contents);

        $this->indexer->index(
            [$uri],
            $workspace->getConfiguration()->getFileExtensions(),
            $workspace->getConfiguration()->getExcludedPathExpressions(),
            true,
            $sender
        );
    }
}
