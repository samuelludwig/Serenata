<?php

namespace Serenata\UserInterface\Command;

use Serenata\Indexing\IndexerInterface;
use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

use Serenata\Utility\FileEvent;
use Serenata\Utility\FileChangeType;
use Serenata\Utility\DidChangeWatchedFilesParams;

/**
 * Handles the "workspace/didChangeWatchedFiles" notification.
 */
final class DidChangeWatchedFilesCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @param StorageInterface $storage
     * @param IndexerInterface $indexer
     */
    public function __construct(StorageInterface $storage, IndexerInterface $indexer)
    {
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
        if ($event->getType() === FileChangeType::DELETED) {
            try {
                $this->storage->delete($this->storage->getFileByUri($event->getUri()));
            } catch (FileNotFoundStorageException $e) {
                return; // Not a known file, then don't remove it either.
            }

            return;
        }

        $this->indexer->index($event->getUri(), false, $sender);
    }
}
