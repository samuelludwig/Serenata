<?php

namespace Serenata\Analysis\Typing;

use Serenata\Analysis\ClearableCacheInterface;

use Serenata\Indexing\Structures;

 /**
  * Decorator for classes implementing {@see FileClasslikeListProviderInterface} that performs caching.
  */
final class FileClasslikeListProviderCachingDecorator implements FileClasslikeListProviderInterface, ClearableCacheInterface
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
