<?php

namespace PhpIntegrator\Analysis;

/**
 * Retrieves a list of available classes.
 */
class CachingClassListProvider extends ClassListProvider
{
    /**
     * @var array
     */
    protected $globalConstantFqcnMap;

    /**
     * @inheritDoc
     */
    protected function getAllForOptionalFile(?string $file): array
    {
        if ($this->globalConstantFqcnMap === null) {
            $this->globalConstantFqcnMap = parent::getGlobalConstantFqcnMap();
        }

        return $this->globalConstantFqcnMap;
    }
}
