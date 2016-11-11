<?php

namespace PhpIntegrator\Analysis;

/**
 * @inheritDoc
 */
class CachingGlobalConstantExistanceChecker extends GlobalConstantExistanceChecker
{
    /**
     * @var array
     */
    protected $globalConstantFqcnMap;

    /**
     * @inheritDoc
     */
    protected function getGlobalConstantFqcnMap()
    {
        if ($this->globalConstantFqcnMap === null) {
            $this->globalConstantFqcnMap = parent::getGlobalConstantFqcnMap();
        }

        return $this->globalConstantFqcnMap;
    }

    /**
     * @return void
     */
    protected function clearCache()
    {
        $this->globalConstantFqcnMap = null;
    }
}
