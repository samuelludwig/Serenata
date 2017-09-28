<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Analysis\ClasslikeListProviderInterface;

use PhpIntegrator\Analysis\Typing\FileClasslikeListProviderInterface;

use PhpIntegrator\Indexing\StorageInterface;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

/**
 * Command that shows a list of available classes, interfaces and traits.
 */
class ClassListCommand extends AbstractCommand
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
