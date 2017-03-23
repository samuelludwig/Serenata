<?php

namespace PhpIntegrator\Analysis\Typing\Resolving;

use UnexpectedValueException;

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
     * @param GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker
     * @param GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker
     * @param NamespaceImportProviderInterface $namespaceImportProviderInterface
     */
    public function __construct(
        GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker,
        GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker,
        NamespaceImportProviderInterface $namespaceImportProviderInterface
    ) {
        $this->globalConstantExistenceChecker = $globalConstantExistenceChecker;
        $this->globalFunctionExistenceChecker = $globalFunctionExistenceChecker;
        $this->namespaceImportProviderInterface = $namespaceImportProviderInterface;
    }

    /**
     * @param FileTypeResolverInterface $typeResolver
     * @param string                    $filePath
     *
     * @throws UnexpectedValueException if no namespaces exist for a file.
     *
     * @return ProjectTypeResolver
     */
    public function create(FileTypeResolverInterface $typeResolver, string $filePath): ProjectTypeResolver
    {
        $namespaces = $this->namespaceImportProviderInterface->getNamespacesForFile($filePath);

        if (empty($namespaces)) {
            throw new UnexpectedValueException(
                'No namespace found, but there should always exist at least one namespace row in the database!'
            );
        }

        return new ProjectTypeResolver(
            $typeResolver,
            $this->globalConstantExistenceChecker,
            $this->globalFunctionExistenceChecker,
            $namespaces
        );
    }
}
