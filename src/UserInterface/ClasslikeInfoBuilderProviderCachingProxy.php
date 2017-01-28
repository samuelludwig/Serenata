<?php

namespace PhpIntegrator\UserInterface;

use Doctrine\Common\Cache\Cache;

use PhpIntegrator\Analysis\ClasslikeInfoBuilderProviderInterface;

/**
 * Proxy for providers that introduces a caching layer.
 */
class ClasslikeInfoBuilderProviderCachingProxy implements ClasslikeInfoBuilderProviderInterface
{
    /**
     * @var ClasslikeInfoBuilderProviderInterface
     */
    protected $provider;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param ClasslikeInfoBuilderProviderInterface $provider
     * @param Cache             $cache
     */
    public function __construct(ClasslikeInfoBuilderProviderInterface $provider, Cache $cache)
    {
        $this->provider = $provider;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawInfo(string $fqcn): ?array
    {
        $cacheId = $this->getCacheId(__FUNCTION__, func_get_args());

        $data = $this->proxyCall(__FUNCTION__, func_get_args());

        $this->rememberCacheIdForFqcn($fqcn, $cacheId);

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawParents(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawChildren(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawInterfaces(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawImplementors(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawTraits(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawTraitUsers(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawConstants(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawProperties(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawMethods(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeTraitAliasesAssoc(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeTraitPrecedencesAssoc(int $id): array
    {
        return $this->proxyCall(__FUNCTION__, func_get_args());
    }

    /**
     * @param mixed $method
     * @param array $arguments
     *
     * @return mixed
     */
    protected function proxyCall(string $method, array $arguments)
    {
        $cacheId = $this->getCacheId($method, $arguments);

        if ($this->cache->contains($cacheId)) {
            return $this->cache->fetch($cacheId);
        }

        $data = call_user_func_array([$this->provider, $method], $arguments);

        $this->cache->save($cacheId, $data);

        return $data;
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return string
     */
    protected function getCacheId(string $method, array $arguments): string
    {
        return $method . '_' . serialize($arguments);
    }

    /**
     * @param string $fqcn
     * @param string $cacheId
     *
     * @return void
     */
    protected function rememberCacheIdForFqcn(string $fqcn, string $cacheId): void
    {
        $cacheMap = $this->getCacheMap();
        $cacheMap[$fqcn][$cacheId] = true;

        $this->saveCacheMap($cacheMap);
    }

    /**
     * @param string $fqcn
     *
     * @return void
     */
    public function clearCacheFor(string $fqcn): void
    {
        $cacheMap = $this->getCacheMap();

        if (isset($cacheMap[$fqcn])) {
            foreach ($cacheMap[$fqcn] as $cacheId => $ignoredValue) {
                $this->cache->delete($cacheId);
            }

            unset($cacheMap[$fqcn]);

            $this->saveCacheMap($cacheMap);
        }
    }

    /**
     * @return array
     */
    protected function getCacheMap(): array
    {
        $cacheIdsCacheId = $this->getCacheIdForFqcnListCacheId();

        // The silence operator isn't actually necessary, except on Windows. In some rare situations, it will complain
        // with a "permission denied" error on the shared cache map file (locking it has no effect either). Usually,
        // however, it will work fine on Windows as well. This way at least these users enjoy caching somewhat instead
        // of having no caching at all. See also https://github.com/Gert-dev/php-integrator-base/issues/185 .
        $cacheMap = @$this->cache->fetch($cacheIdsCacheId);

        return $cacheMap ?: [];
    }

    /**
     * @param array $cacheMap
     *
     * @return void
     */
    protected function saveCacheMap(array $cacheMap): void
    {
        $cacheIdsCacheId = $this->getCacheIdForFqcnListCacheId();

        // Silenced for the same reason as above.
        @$this->cache->save($cacheIdsCacheId, $cacheMap);
    }

    /**
     * @return string
     */
    protected function getCacheIdForFqcnListCacheId(): string
    {
        return __CLASS__ . '_fqcn';
    }
}
