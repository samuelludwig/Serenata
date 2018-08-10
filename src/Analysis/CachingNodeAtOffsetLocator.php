<?php

namespace Serenata\Analysis;

use Serenata\Common\Position;

use Serenata\Utility\TextDocumentItem;

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
    public function locate(TextDocumentItem $textDocumentItem, Position $position): NodeAtOffsetLocatorResult
    {
        $cacheKey = md5($textDocumentItem->getText()) . '_' . $position->getLine() . '_' . $position->getCharacter();

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->delegate->locate($textDocumentItem, $position);
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
