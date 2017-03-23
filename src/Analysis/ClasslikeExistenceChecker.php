<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Checks if a classlike exists.
 */
class ClasslikeExistenceChecker implements ClasslikeExistenceCheckerInterface
{
    /**
     * @var IndexDatabase
     */
    private $indexDatabase;

    /**
     * @param IndexDatabase $indexDatabase
     */
    public function __construct(IndexDatabase $indexDatabase)
    {
        $this->indexDatabase = $indexDatabase;
    }

    /**
     * @inheritDoc
     */
    public function doesClassExist(string $fqcn): bool
    {
        $classlikeFqcnMap = $this->getClasslikeFqcnMap();

        return isset($classlikeFqcnMap[$fqcn]);
    }

    /**
     * @return array
     */
    protected function getClasslikeFqcnMap(): array
    {
        $classlikeFqcnMap = [];

        foreach ($this->indexDatabase->getAllStructuresRawInfo(null) as $element) {
            $classlikeFqcnMap[$element['fqcn']] = true;
        }

        return $classlikeFqcnMap;
    }
}
