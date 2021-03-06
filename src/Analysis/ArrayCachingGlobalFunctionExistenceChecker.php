<?php

namespace Serenata\Analysis;

use Serenata\NameQualificationUtilities\FunctionPresenceIndicatorInterface;

/**
 * Delegates classlike existence checking to another object and adds a caching wrapper.
 */
final class ArrayCachingGlobalFunctionExistenceChecker implements
    FunctionPresenceIndicatorInterface,
    ClearableCacheInterface
{
    /**
     * @var FunctionPresenceIndicatorInterface
     */
    private $delegate;

    /**
     * @var array<string,bool>
     */
    private $cache = [];

    /**
     * @param FunctionPresenceIndicatorInterface $delegate
     */
    public function __construct(FunctionPresenceIndicatorInterface $delegate)
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
