<?php

namespace PhpIntegrator\Analysis\Typing\Localization;

use PhpIntegrator\Analysis\Typing\NamespaceImportProviderInterface;

use PhpIntegrator\Analysis\Typing\Resolving\FileLineNamespaceDeterminerFactory;

/**
 * Factory that creates instances of {@see FileTypeLocalizer}.
 */
class FileTypeLocalizerFactory
{
    /**
     * @var TypeLocalizer
     */
    private $typeLocalizer;

    /**
     * @var NamespaceImportProviderInterface
     */
    private $namespaceImportProviderInterface;

    /**
     * @var FileLineNamespaceDeterminerFactory
     */
    private $fileLineNamespaceDeterminerFactory;

    /**
     * @param TypeLocalizer                      $typeLocalizer
     * @param NamespaceImportProviderInterface   $namespaceImportProviderInterface
     * @param FileLineNamespaceDeterminerFactory $fileLineNamespaceDeterminerFactory
     */
    public function __construct(
        TypeLocalizer $typeLocalizer,
        NamespaceImportProviderInterface $namespaceImportProviderInterface,
        FileLineNamespaceDeterminerFactory $fileLineNamespaceDeterminerFactory
    ) {
        $this->typeLocalizer = $typeLocalizer;
        $this->namespaceImportProviderInterface = $namespaceImportProviderInterface;
        $this->fileLineNamespaceDeterminerFactory = $fileLineNamespaceDeterminerFactory;
    }

    /**
     * @param string $filePath
     *
     * @return FileTypeLocalizer
     */
    public function create(string $filePath): FileTypeLocalizer
    {
        return new FileTypeLocalizer(
            $this->typeLocalizer,
            $this->fileLineNamespaceDeterminerFactory->create($filePath),
            $this->namespaceImportProviderInterface->getUseStatementsForFile($filePath)
        );
    }
}
