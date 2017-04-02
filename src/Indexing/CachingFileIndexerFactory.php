<?php

namespace PhpIntegrator\Indexing;

use Doctrine\Common\Cache\Cache;

/**
 * File indexer factory that caches the results of a delegate.
 */
class CachingFileIndexerFactory implements FileIndexerFactoryInterface
{
    /**
     * @var FileIndexerFactoryInterface
     */
    private $delegate;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param FileIndexerFactoryInterface $delegate
     * @param Cache                       $cache
     */
    public function __construct(FileIndexerFactoryInterface $delegate, Cache $cache)
    {
        $this->delegate = $delegate;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function create(string $filePath): FileIndexerInterface
    {
        if ($this->cache->contains($filePath)) {
            return $this->cache->fetch($filePath);
        }

        $result = $this->delegate->create($filePath);

        $this->cache->save($filePath, $result);

        return $result;
    }
}
