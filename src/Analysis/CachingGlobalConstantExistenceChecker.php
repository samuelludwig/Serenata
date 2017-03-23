<?php

namespace PhpIntegrator\Analysis;

/**
 * @inheritDoc
 */
class CachingGlobalConstantExistenceChecker extends GlobalConstantExistenceChecker implements ClearableCacheInterface
{
    /**
     * @var array
     */
    private $globalConstantFqcnMap;

    /**
     * @inheritDoc
     */
    protected function getGlobalConstantFqcnMap(): array
    {
        if ($this->globalConstantFqcnMap === null) {
            $this->globalConstantFqcnMap = parent::getGlobalConstantFqcnMap();
        }

        return $this->globalConstantFqcnMap;
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->globalConstantFqcnMap = null;
    }
}
