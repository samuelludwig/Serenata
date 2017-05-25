<?php

namespace PhpIntegrator\Analysis\Typing;

use PhpIntegrator\Analysis\ClearableCacheInterface;

 /**
  * Decorator for classes implementing the {@see FileClassListProviderInterface} interface that performs caching.
  */
class FileClassListProviderCachingDecorator implements FileClassListProviderInterface, ClearableCacheInterface
{
    /**
     * @var FileClassListProviderInterface
     */
    private $fileClassListProviderInterface;

    /**
     * @var array
     */
    private $cache;

    /**
     * @param FileClassListProviderInterface $fileClassListProviderInterface
     */
    public function __construct(FileClassListProviderInterface $fileClassListProviderInterface)
    {
        $this->fileClassListProviderInterface = $fileClassListProviderInterface;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        if (!isset($this->cache[null])) {
            $this->cache[null] = $this->fileClassListProviderInterface->getAll();
        }

        return $this->cache[null];
    }

    /**
     * @inheritDoc
     */
    public function getAllForFile(string $filePath): array
    {
        if (!isset($this->cache[$filePath])) {
            $this->cache[$filePath] = $this->fileClassListProviderInterface->getAllForFile($filePath);
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
