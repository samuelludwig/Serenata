<?php

namespace PhpIntegrator\Analysis\Typing\Resolving;

use PhpIntegrator\Analysis\Typing\NamespaceImportProviderInterface;

/**
 * Factory that creates instances of {@see FileLineNamespaceDeteminer}.
 */
class FileLineNamespaceDeterminerFactory
{
    /**
     * @var NamespaceImportProviderInterface
     */
    private $namespaceImportProviderInterface;

    /**
     * @param NamespaceImportProviderInterface $namespaceImportProviderInterface
     */
    public function __construct(NamespaceImportProviderInterface $namespaceImportProviderInterface)
    {
        $this->namespaceImportProviderInterface = $namespaceImportProviderInterface;
    }

    /**
     * @param string $filePath
     *
     * @return FileLineNamespaceDeterminer
     */
    public function create(string $filePath): FileLineNamespaceDeterminer
    {
        $namespaces = $this->namespaceImportProviderInterface->getNamespacesForFile($filePath);

        return new FileLineNamespaceDeterminer($namespaces);
    }
}
