<?php

namespace Serenata\Analysis;

/**
 * Caching locator that delegates to another object and caches the result.
 */
class CachingNodeAtOffsetLocator implements NodeAtOffsetLocatorInterface, ClearableCacheInterface
{
    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $delegate;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param NodeAtOffsetLocatorInterface $delegate
     */
    public function __construct(NodeAtOffsetLocatorInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function locate(string $code, int $position): NodeAtOffsetLocatorResult
    {
        $cacheKey = md5($code) . '_' . $position;

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->delegate->locate($code, $position);
        }

        return $this->cache[$cacheKey];
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
