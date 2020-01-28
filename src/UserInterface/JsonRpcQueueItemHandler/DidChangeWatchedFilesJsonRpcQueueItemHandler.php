<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\IndexerInterface;
use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

use Serenata\Utility\FileEvent;
use Serenata\Utility\FileChangeType;
use Serenata\Utility\DidChangeWatchedFilesParams;

/**
 * Handles the "workspace/didChangeWatchedFiles" notification.
 */
final class DidChangeWatchedFilesJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
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
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams();

        if ($parameters === null || $parameters === '') {
            throw new InvalidArgumentsException('Missing parameters for didChangeWatchedFiles request');
        }

        $this->handle($this->createParamsFromRawArray($parameters), $queueItem->getJsonRpcMessageSender());

        // This is a notification that doesn't expect a response.
        $deferred = new Deferred();
        $deferred->resolve(null);

        return $deferred->promise();
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
     * @param JsonRpcMessageSenderInterface $sender
     */
    public function handle(DidChangeWatchedFilesParams $parameters, JsonRpcMessageSenderInterface $sender): void
    {
        foreach ($parameters->getChanges() as $change) {
            $this->handleFileEvent($change, $sender);
        }
    }

    /**
     * @param FileEvent                      $event
     * @param JsonRpcMessageSenderInterface $sender
     */
    public function handleFileEvent(FileEvent $event, JsonRpcMessageSenderInterface $sender): void
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
