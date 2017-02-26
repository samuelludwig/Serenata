<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Checks if a global constant exists.
 */
class GlobalConstantExistenceChecker implements GlobalConstantExistenceCheckerInterface
{
    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

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
    public function exists(string $fqcn): bool
    {
        $globalConstantFqcnMap = $this->getGlobalConstantFqcnMap();

        return isset($globalConstantFqcnMap[$fqcn]);
    }

    /**
     * @return array
     */
    protected function getGlobalConstantFqcnMap(): array
    {
        $globalConstantFqcnMap = [];

        foreach ($this->indexDatabase->getGlobalConstants() as $element) {
            $globalConstantFqcnMap[$element['fqcn']] = true;
        }

        return $globalConstantFqcnMap;
    }
}
