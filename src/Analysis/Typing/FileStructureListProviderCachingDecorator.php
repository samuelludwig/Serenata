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
    private $structureClassListProviderInterface;

    /**
     * @var array
     */
    private $cache;

    /**
     * @param FileStructureListProviderInterface $structureClassListProviderInterface
     */
    public function __construct(FileStructureListProviderInterface $structureClassListProviderInterface)
    {
        $this->fileStructureListProviderInterface = $structureClassListProviderInterface;
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
