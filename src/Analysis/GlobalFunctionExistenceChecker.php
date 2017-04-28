<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\NameQualificationUtilities\FunctionPresenceIndicatorInterface;

/**
 * Checks if a global function exists.
 */
class GlobalFunctionExistenceChecker implements FunctionPresenceIndicatorInterface
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
    public function exists(string $fqcn): bool
    {
        $globalFunctionsFqcnMap = $this->getGlobalFunctionsFqcnMap();

        return isset($globalFunctionsFqcnMap[$fqcn]);
    }

    /**
     * @inheritDoc
     */
    public function isPresent(string $fullyQualifiedName): bool
    {
        return $this->exists($fullyQualifiedName);
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
