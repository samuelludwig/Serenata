<?php

namespace PhpIntegrator\Analysis;

/**
 * @inheritDoc
 */
class CachingGlobalFunctionExistanceChecker extends GlobalFunctionExistanceChecker
{
    /**
     * @var array
     */
    protected $globalFunctionsFqcnMap;

    /**
     * @inheritDoc
     */
    protected function getGlobalFunctionsFqcnMap()
    {
        if ($this->globalFunctionsFqcnMap === null) {
            $this->globalFunctionsFqcnMap = parent::getGlobalFunctionsFqcnMap();
        }

        return $this->globalFunctionsFqcnMap;
    }

    /**
     * @return void
     */
    protected function clearCache()
    {
        $this->globalFunctionsFqcnMap = null;
    }
}
