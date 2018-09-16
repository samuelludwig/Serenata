<?php

namespace Serenata\UserInterface\Command;

use UnexpectedValueException;

use Serenata\Indexing\Indexer;
use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

use Serenata\Utility\FileEvent;
use Serenata\Utility\FileChangeType;
use Serenata\Utility\DidChangeWatchedFilesParams;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * Handles the "workspace/didChangeWatchedFiles" notification.
 */
final class DidChangeWatchedFilesCommand extends AbstractCommand
{
    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @param ActiveWorkspaceManager $activeWorkspaceManager
     * @param StorageInterface       $storage
     * @param Indexer                $indexer
     */
    public function __construct(
        ActiveWorkspaceManager $activeWorkspaceManager,
        StorageInterface $storage,
        Indexer $indexer
    ) {
        $this->activeWorkspaceManager = $activeWorkspaceManager;
        $this->storage = $storage;
        $this->indexer = $indexer;
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

        $this->handle($this->createParamsFromRawArray($parameters), $queueItem->getJsonRpcResponseSender());

        return null; // This is a notification that doesn't expect a response.
    }

    /**
     * @param array $parameters
     *
     * @return DidChangeWatchedFilesParams
     */
    private function createParamsFromRawArray(array $parameters): DidChangeWatchedFilesParams
    {
        return new DidChangeWatchedFilesParams($this->createFileEventsFromRawArray($parameters['changes']));
    }

    /**
     * @param array $fileEvents
     *
     * @return FileEvent[]
     */
    private function createFileEventsFromRawArray(array $fileEvents): array
    {
        return array_map(function (array $rawFileEvent): FileEvent {
            return new FileEvent($rawFileEvent['uri'], $rawFileEvent['type']);
        }, $fileEvents);
    }

    /**
     * @param DidChangeWatchedFilesParams    $parameters
     * @param JsonRpcResponseSenderInterface $sender
     */
    public function handle(DidChangeWatchedFilesParams $parameters, JsonRpcResponseSenderInterface $sender): void
    {
        foreach ($parameters->getChanges() as $change) {
            $this->handleFileEvent($change, $sender);
        }
    }

    /**
     * @param FileEvent                      $event
     * @param JsonRpcResponseSenderInterface $sender
     */
    public function handleFileEvent(FileEvent $event, JsonRpcResponseSenderInterface $sender): void
    {
        $workspace = $this->activeWorkspaceManager->getActiveWorkspace();

        if (!$workspace) {
            throw new UnexpectedValueException(
                'Cannot handle file change event when there is no active workspace, did you send an initialize ' .
                'request first?'
            );
        }

        if ($event->getType() === FileChangeType::DELETED) {
            try {
                $this->storage->delete($this->storage->getFileByPath($event->getUri()));
            } catch (FileNotFoundStorageException $e) {
                return; // Not a known file, then don't remove it either.
            }

            return;
        }

        $this->indexer->index(
            [$event->getUri()],
            $workspace->getConfiguration()->getFileExtensions(),
            $workspace->getConfiguration()->getExcludedPathExpressions(),
            false,
            $sender
        );
    }
}
