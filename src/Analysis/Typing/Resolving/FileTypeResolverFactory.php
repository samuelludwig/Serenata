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
     * @param TypeResolverInterface            $typeResolver
     * @param NamespaceImportProviderInterface $namespaceImportProviderInterface
     */
    public function __construct(
        TypeResolverInterface $typeResolver,
        NamespaceImportProviderInterface $namespaceImportProviderInterface
    ) {
        $this->typeResolver = $typeResolver;
        $this->namespaceImportProviderInterface = $namespaceImportProviderInterface;
    }

    /**
     * @inheritDoc
     */
    public function create(string $filePath): FileTypeResolver
    {
        $namespaces = $this->namespaceImportProviderInterface->getNamespacesForFile($filePath);

        if (empty($namespaces)) {
            throw new UnexpectedValueException(
                'No namespace found for "' . $filePath .
                '", but there should always exist at least one namespace row in the database!'
            );
        }

        $useStatements = $this->namespaceImportProviderInterface->getUseStatementsForFile($filePath);

        return new FileTypeResolver($this->typeResolver, $namespaces, $useStatements);
    }
}
