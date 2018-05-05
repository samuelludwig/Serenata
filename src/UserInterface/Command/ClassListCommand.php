<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\ClasslikeListProviderInterface;

use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Indexing\StorageInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that shows a list of available classes, interfaces and traits.
 */
final class ClassListCommand extends AbstractCommand
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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        $filePath = $arguments['file'] ?? null;

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            ($filePath !== null) ? $this->getAllForFilePath($filePath) : $this->getAll()
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
     * @param string $filePath
     *
     * @return array
     */
    public function getAllForFilePath(string $filePath): array
    {
        $file = $this->storage->getFileByPath($filePath);

        return $this->fileClasslikeListProvider->getAllForFile($file);
    }
}
