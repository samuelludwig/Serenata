<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Checks if a global function exists.
 */
class GlobalFunctionExistenceChecker implements GlobalFunctionExistenceCheckerInterface
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
    public function doesGlobalFunctionExist(string $fqcn): bool
    {
        $globalFunctionsFqcnMap = $this->getGlobalFunctionsFqcnMap();

        return isset($globalFunctionsFqcnMap[$fqcn]);
    }

    /**
     * @return array
     */
    protected function getGlobalFunctionsFqcnMap(): array
    {
        $globalFunctionsFqcnMap = [];

        foreach ($this->indexDatabase->getGlobalFunctions() as $element) {
            $globalFunctionsFqcnMap[$element['fqcn']] = true;
        }

        return $globalFunctionsFqcnMap;
    }
}
