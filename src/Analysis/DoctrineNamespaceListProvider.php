<?php

namespace Serenata\Analysis;

use Serenata\Analysis\Conversion\NamespaceConverter;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

/**
 * Retrieves a list of available classlikes via Doctrine.
 */
final class DoctrineNamespaceListProvider implements FileNamespaceListProviderInterface, NamespaceListProviderInterface
{
    /**
     * @var NamespaceConverter
     */
    private $namespaceConverter;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param NamespaceConverter $namespaceConverter
     * @param ManagerRegistry    $managerRegistry
     */
    public function __construct(NamespaceConverter $namespaceConverter, ManagerRegistry $managerRegistry)
    {
        $this->namespaceConverter = $namespaceConverter;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        $namespaces = $this->managerRegistry->getRepository(Structures\FileNamespace::class)->findAll();

        return $this->mapNamespaces($namespaces);
    }

    /**
     * @inheritDoc
     */
    public function getAllForFile(Structures\File $file): array
    {
        $namespaces = $this->managerRegistry->getRepository(Structures\FileNamespace::class)->findBy([
            'file' => $file,
        ]);

        return $this->mapNamespaces($namespaces);
    }

    /**
     * @param Structures\FileNamespace[] $namespaces
     *
     * @return array<string,array<string,mixed>>
     */
    private function mapNamespaces(array $namespaces): array
    {
        $result = [];

        foreach ($namespaces as $element) {
            $result[$element->getId()] = $this->namespaceConverter->convert($element);
        }

        return $result;
    }
}
