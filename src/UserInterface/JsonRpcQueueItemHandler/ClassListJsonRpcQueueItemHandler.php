<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Analysis\ClasslikeListProviderInterface;

use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Indexing\StorageInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * JsonRpcQueueItemHandlerthat shows a list of available classes, interfaces and traits.
 *
 * @deprecated Will be removed as soon as all functionality this facilitates is implemented as LSP-compliant requests.
 */
final class ClassListJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var ClasslikeListProviderInterface
     */
    private $classlikeListProvider;

    /**
     * @var FileClasslikeListProviderInterface
     */
    private $fileClasslikeListProvider;

    /**
     * @param StorageInterface                   $storage
     * @param ClasslikeListProviderInterface     $classlikeListProvider
     * @param FileClasslikeListProviderInterface $fileClasslikeListProvider
     */
    public function __construct(
        StorageInterface $storage,
        ClasslikeListProviderInterface $classlikeListProvider,
        FileClasslikeListProviderInterface $fileClasslikeListProvider
    ) {
        $this->storage = $storage;
        $this->classlikeListProvider = $classlikeListProvider;
        $this->fileClasslikeListProvider = $fileClasslikeListProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        $uri = $arguments['uri'] ?? null;

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            ($uri !== null) ? $this->getAllForFilePath($uri) : $this->getAll()
        );
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->classlikeListProvider->getAll();
    }

    /**
     * @param string $uri
     *
     * @return array
     */
    public function getAllForFilePath(string $uri): array
    {
        $file = $this->storage->getFileByUri($uri);

        return $this->fileClasslikeListProvider->getAllForFile($file);
    }
}
