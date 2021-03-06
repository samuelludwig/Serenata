<?php

namespace Serenata\Analysis;

use Serenata\NameQualificationUtilities\ConstantPresenceIndicatorInterface;

/**
 * Delegates classlike existence checking to another object and adds a caching wrapper.
 */
final class ArrayCachingGlobalConstantExistenceChecker implements
    ConstantPresenceIndicatorInterface,
    ClearableCacheInterface
{
    /**
     * @var ConstantPresenceIndicatorInterface
     */
    private $delegate;

    /**
     * @var array<string,bool>
     */
    private $cache = [];

    /**
     * @param ConstantPresenceIndicatorInterface $delegate
     */
    public function __construct(ConstantPresenceIndicatorInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function isPresent(string $fqcn): bool
    {
        if (!isset($this->cache[$fqcn])) {
            $this->cache[$fqcn] = $this->delegate->isPresent($fqcn);
        }

        return $this->cache[$fqcn];
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
