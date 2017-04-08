<?php

namespace PhpIntegrator\Analysis\Typing\Resolving;

use PhpIntegrator\Analysis\GlobalConstantExistenceCheckerInterface;
use PhpIntegrator\Analysis\GlobalFunctionExistenceCheckerInterface;

use PhpIntegrator\Analysis\Typing\NamespaceImportProviderInterface;

/**
 * Factory that creates instances of {@see ProjectTypeResolver}.
 */
class ProjectTypeResolverFactory
{
    /**
     * @var GlobalConstantExistenceCheckerInterface
     */
    private $globalConstantExistenceChecker;

    /**
     * @var GlobalFunctionExistenceCheckerInterface
     */
    private $globalFunctionExistenceChecker;

    /**
     * @var NamespaceImportProviderInterface
     */
    private $namespaceImportProviderInterface;

    /**
     * @var FileLineNamespaceDeterminerFactory
     */
    private $fileLineNamespaceDeterminerFactory;

    /**
     * @param GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker
     * @param GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker
     * @param NamespaceImportProviderInterface        $namespaceImportProviderInterface
     * @param FileLineNamespaceDeterminerFactory      $fileLineNamespaceDeterminerFactory
     */
    public function __construct(
        GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker,
        GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker,
        NamespaceImportProviderInterface $namespaceImportProviderInterface,
        FileLineNamespaceDeterminerFactory $fileLineNamespaceDeterminerFactory
    ) {
        $this->globalConstantExistenceChecker = $globalConstantExistenceChecker;
        $this->globalFunctionExistenceChecker = $globalFunctionExistenceChecker;
        $this->namespaceImportProviderInterface = $namespaceImportProviderInterface;
        $this->fileLineNamespaceDeterminerFactory = $fileLineNamespaceDeterminerFactory;
    }

    /**
     * @param FileTypeResolverInterface $typeResolver
     * @param string                    $filePath
     *
     * @return ProjectTypeResolver
     */
    public function create(FileTypeResolverInterface $typeResolver, string $filePath): ProjectTypeResolver
    {
        return new ProjectTypeResolver(
            $typeResolver,
            $this->globalConstantExistenceChecker,
            $this->globalFunctionExistenceChecker,
            $this->fileLineNamespaceDeterminerFactory->create($filePath)
        );
    }
}
