<?php

namespace PhpIntegrator\Analysis\Typing;

use PhpIntegrator\Analysis\ClearableCacheInterface;

 /**
  * Decorator for classes implementing {@see FileStructureListProviderInterface} that performs caching.
  */
class FileStructureListProviderCachingDecorator implements FileStructureListProviderInterface, ClearableCacheInterface
{
    /**
     * @var FileStructureListProviderInterface
     */
    private $StructureClassListProviderInterface;

    /**
     * @var array
     */
    private $cache;

    /**
     * @param FileStructureListProviderInterface $StructureClassListProviderInterface
     */
    public function __construct(FileStructureListProviderInterface $StructureClassListProviderInterface)
    {
        $this->fileStructureListProviderInterface = $StructureClassListProviderInterface;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        if (!isset($this->cache[null])) {
            $this->cache[null] = $this->fileStructureListProviderInterface->getAll();
        }

        return $this->cache[null];
    }

    /**
     * @inheritDoc
     */
    public function getAllForFile(string $filePath): array
    {
        if (!isset($this->cache[$filePath])) {
            $this->cache[$filePath] = $this->fileStructureListProviderInterface->getAllForFile($filePath);
        }

        return $this->cache[$filePath];
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
