<?php

namespace PhpIntegrator\Analysis\Typing\Resolving;

use LogicException;

use PhpIntegrator\Utility\NamespaceData;

/**
 * Determines the namespace of the specified location in a file.
 */
class FileLineNamespaceDeterminer
{
    /**
     * @var array
     */
    private $namespaces;

    /**
     * @param NamespaceData[] $namespaces
     */
    public function __construct(array $namespaces)
    {
        $this->namespaces = $namespaces;
    }

    /**
     * @param int $line
     *
     * @return NamespaceData
     */
    public function determine(int $line): NamespaceData
    {
        foreach ($this->namespaces as $namespace) {
            if ($namespace->containsLine($line)) {
                return $namespace;
            }
        }

        throw new LogicException('Sanity check failed: should always have at least one namespace structure');
    }
}
