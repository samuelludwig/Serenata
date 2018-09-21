<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\NamespaceListProviderInterface;
use Serenata\Analysis\FileNamespaceListProviderInterface;

use Serenata\Indexing\StorageInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that shows a list of available namespace.
 */
final class NamespaceListCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var NamespaceListProviderInterface
     */
    private $namespaceListProvider;

    /**
     * @var FileNamespaceListProviderInterface
     */
    private $fileNamespaceListProvider;

    /**
     * @param StorageInterface                   $storage
     * @param NamespaceListProviderInterface     $namespaceListProvider
     * @param FileNamespaceListProviderInterface $fileNamespaceListProvider
     */
    public function __construct(
        StorageInterface $storage,
        NamespaceListProviderInterface $namespaceListProvider,
        FileNamespaceListProviderInterface $fileNamespaceListProvider
    ) {
        $this->storage = $storage;
        $this->namespaceListProvider = $namespaceListProvider;
        $this->fileNamespaceListProvider = $fileNamespaceListProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->getNamespaceList($arguments['uri'] ?? null)
        );
    }

    /**
     * @param string|null $uri
     *
     * @return array
     */
    public function getNamespaceList(?string $uri = null): array
    {
        $criteria = [];

        if ($uri !== null) {
            $file = $this->storage->getFileByUri($uri);

            return $this->fileNamespaceListProvider->getAllForFile($file);
        }

        return $this->namespaceListProvider->getAll();
    }
}
