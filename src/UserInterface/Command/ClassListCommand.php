<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Analysis\StructureListProviderInterface;

use PhpIntegrator\Analysis\Typing\FileStructureListProviderInterface;

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
     * @var StructureListProviderInterface
     */
    private $structureListProvider;

    /**
     * @var FileStructureListProviderInterface
     */
    private $fileStructureListProvider;

    /**
     * @param StorageInterface                   $storage
     * @param StructureListProviderInterface     $structureListProvider
     * @param FileStructureListProviderInterface $fileStructureListProvider
     */
    public function __construct(
        StorageInterface $storage,
        StructureListProviderInterface $structureListProvider,
        FileStructureListProviderInterface $fileStructureListProvider
    ) {
        $this->storage = $storage;
        $this->structureListProvider = $structureListProvider;
        $this->fileStructureListProvider = $fileStructureListProvider;
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
        return $this->structureListProvider->getAll();
    }

    /**
     * @param string $filePath
     *
     * @return array
     */
    public function getAllForFilePath(string $filePath): array
    {
        $file = $this->storage->getFileByPath($filePath);

        return $this->fileStructureListProvider->getAllForFile($file);
    }
}
