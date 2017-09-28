<?php

namespace PhpIntegrator\Analysis\Typing;

use PhpIntegrator\Analysis\ClearableCacheInterface;

use PhpIntegrator\Indexing\Structures;

 /**
  * Decorator for classes implementing {@see FileClasslikeListProviderInterface} that performs caching.
  */
class FileClasslikeListProviderCachingDecorator implements FileClasslikeListProviderInterface, ClearableCacheInterface
{
    /**
     * @var FileClasslikeListProviderInterface
     */
    private $classlikeClassListProviderInterface;

    /**
     * @var array
     */
    private $cache;

    /**
     * @param FileClasslikeListProviderInterface $classlikeClassListProviderInterface
     */
    public function __construct(FileClasslikeListProviderInterface $classlikeClassListProviderInterface)
    {
        $this->fileClasslikeListProviderInterface = $classlikeClassListProviderInterface;
    }

    /**
     * @inheritDoc
     */
    public function getAllForFile(Structures\File $file): array
    {
        $filePath = $file->getPath();

        if (!isset($this->cache[$filePath])) {
            $this->cache[$filePath] = $this->fileClasslikeListProviderInterface->getAllForFile($file);
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
