<?php

namespace Serenata\Analysis\Typing;

use Serenata\Analysis\ClearableCacheInterface;

use Serenata\Indexing\Structures;

 /**
  * Decorator for classes implementing {@see FileClasslikeListProviderInterface} that performs caching.
  */
final class FileClasslikeListProviderCachingDecorator implements
    FileClasslikeListProviderInterface,
    ClearableCacheInterface
{
    /**
     * @var FileClasslikeListProviderInterface
     */
    private $fileClasslikeListProviderInterface;

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
        $uri = $file->getUri();

        if (!isset($this->cache[$uri])) {
            $this->cache[$uri] = $this->fileClasslikeListProviderInterface->getAllForFile($file);
        }

        return $this->cache[$uri];
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
