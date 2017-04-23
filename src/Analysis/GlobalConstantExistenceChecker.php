<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\NameQualificationUtilities\ConstantPresenceIndicatorInterface;

/**
 * Checks if a global constant exists.
 */
class GlobalConstantExistenceChecker implements
    GlobalConstantExistenceCheckerInterface,
    ConstantPresenceIndicatorInterface
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
        $globalConstantFqcnMap = $this->getGlobalConstantFqcnMap();

        return isset($globalConstantFqcnMap[$fqcn]);
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
    protected function getGlobalConstantFqcnMap(): array
    {
        $globalConstantFqcnMap = [];

        foreach ($this->indexDatabase->getGlobalConstants() as $element) {
            $globalConstantFqcnMap[$element['fqcn']] = true;
        }

        return $globalConstantFqcnMap;
    }
}
