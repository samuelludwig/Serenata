<?php

namespace PhpIntegrator\Analysis\Typing;

use PhpIntegrator\Analysis\ClearableCacheInterface;

/**
 * Namespace import provider that delegates its functionality to another object and adds a caching layer on top of it.
 */
class CachingNamespaceImportProvider implements NamespaceImportProviderInterface, ClearableCacheInterface
{
    /**
     * @var NamespaceImportProviderInterface
     */
    private $delegate;

    /**
     * @var array
     */
    private $cache;

    /**
     * @param NamespaceImportProviderInterface $delegate
     */
    public function __construct(NamespaceImportProviderInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function getNamespacesForFile(string $filePath): array
    {
        $id = __METHOD__ . $filePath;

        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->delegate->getNamespacesForFile($filePath);
        }

        return $this->cache[$id];
    }

    /**
     * @inheritDoc
     */
    public function getUseStatementsForFile(string $filePath): array
    {
        $id = __METHOD__ . $filePath;

        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->delegate->getUseStatementsForFile($filePath);
        }

        return $this->cache[$id];
    }

    /**
     * @inheritDoc
     */
    public function getNamespaces(): array
    {
        $id = __METHOD__ . $filePath;

        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->delegate->getNamespaces();
        }

        return $this->cache[$id];
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
