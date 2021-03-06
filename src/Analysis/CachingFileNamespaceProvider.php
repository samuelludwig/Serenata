<?php

namespace Serenata\Analysis;

use Serenata\NameQualificationUtilities\Namespace_;
use Serenata\NameQualificationUtilities\FileNamespaceProviderInterface;

/**
 * Delegates namespace provision to a delegate and adds a caching layer on top of it.
 */
final class CachingFileNamespaceProvider implements FileNamespaceProviderInterface, ClearableCacheInterface
{
    /**
     * @var FileNamespaceProviderInterface
     */
    private $delegate;

    /**
     * @var array<string,array<Namespace_>>
     */
    private $cache = [];

    /**
     * @param FileNamespaceProviderInterface $delegate
     */
    public function __construct(FileNamespaceProviderInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function provide(string $file): array
    {
        if (!isset($this->cache[$file])) {
            $this->cache[$file] = $this->delegate->provide($file);
        }

        return $this->cache[$file];
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
