<?php

namespace PhpIntegrator\Analysis\Typing\Resolving;

use UnexpectedValueException;

use PhpIntegrator\Analysis\Typing\NamespaceImportProviderInterface;

/**
 * Factory that creates instances of {@see FileTypeResolver}.
 */
class FileTypeResolverFactory implements FileTypeResolverFactoryInterface
{
    /**
     * @var TypeResolverInterface
     */
    private $typeResolver;

    /**
     * @var NamespaceImportProviderInterface
     */
    private $namespaceImportProviderInterface;

    /**
     * @var FileLineNamespaceDeterminerFactory
     */
    private $fileLineNamespaceDeterminerFactory;

    /**
     * @param TypeResolverInterface              $typeResolver
     * @param NamespaceImportProviderInterface   $namespaceImportProviderInterface
     * @param FileLineNamespaceDeterminerFactory $fileLineNamespaceDeterminerFactory
     */
    public function __construct(
        TypeResolverInterface $typeResolver,
        NamespaceImportProviderInterface $namespaceImportProviderInterface,
        FileLineNamespaceDeterminerFactory $fileLineNamespaceDeterminerFactory
    ) {
        $this->typeResolver = $typeResolver;
        $this->namespaceImportProviderInterface = $namespaceImportProviderInterface;
        $this->fileLineNamespaceDeterminerFactory = $fileLineNamespaceDeterminerFactory;
    }

    /**
     * @inheritDoc
     */
    public function create(string $filePath): FileTypeResolver
    {
        return new FileTypeResolver(
            $this->typeResolver,
            $this->fileLineNamespaceDeterminerFactory->create($filePath),
            $this->namespaceImportProviderInterface->getUseStatementsForFile($filePath)
        );
    }
}
