<?php

namespace PhpIntegrator\Analysis;

/**
 * @inheritDoc
 */
class CachingGlobalFunctionExistenceChecker extends GlobalFunctionExistenceChecker implements ClearableCacheInterface
{
    /**
     * @var array
     */
    private $globalFunctionsFqcnMap;

    /**
     * @inheritDoc
     */
    protected function getGlobalFunctionsFqcnMap(): array
    {
        if ($this->globalFunctionsFqcnMap === null) {
            $this->globalFunctionsFqcnMap = parent::getGlobalFunctionsFqcnMap();
        }

        return $this->globalFunctionsFqcnMap;
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->globalFunctionsFqcnMap = null;
    }
}
