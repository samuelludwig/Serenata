<?php

namespace PhpIntegrator\Analysis;

/**
 * @inheritDoc
 */
class CachingClasslikeExistanceChecker extends ClasslikeExistanceChecker
{
    /**
     * @var array
     */
    protected $classlikeFqcnMap;

    /**
     * @inheritDoc
     */
    protected function getClasslikeFqcnMap()
    {
        if ($this->classlikeFqcnMap === null) {
            $this->classlikeFqcnMap = parent::getClasslikeFqcnMap();
        }

        return $this->classlikeFqcnMap;
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->classlikeFqcnMap = null;
    }
}
