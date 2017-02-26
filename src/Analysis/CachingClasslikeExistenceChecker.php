<?php

namespace PhpIntegrator\Analysis;

/**
 * @inheritDoc
 */
class CachingClasslikeExistenceChecker extends ClasslikeExistenceChecker implements ClearableCacheInterface
{
    /**
     * @var array
     */
    protected $classlikeFqcnMap;

    /**
     * @inheritDoc
     */
    protected function getClasslikeFqcnMap(): array
    {
        if ($this->classlikeFqcnMap === null) {
            $this->classlikeFqcnMap = parent::getClasslikeFqcnMap();
        }

        return $this->classlikeFqcnMap;
    }

    /**
     * @inheritDoc
     */
    public function clearCache(): void
    {
        $this->classlikeFqcnMap = null;
    }
}
