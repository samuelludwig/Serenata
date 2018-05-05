<?php

namespace Serenata\Analysis;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\NameQualificationUtilities\Import;
use Serenata\NameQualificationUtilities\Namespace_;
use Serenata\NameQualificationUtilities\FileNamespaceProviderInterface;

/**
 * Provides a list of namespaces and imports for a file based on data provided by a storage provider.
 */
final class StorageFileNamespaceProvider implements FileNamespaceProviderInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function provide(string $file): array
    {
        return $this->mapNamespaces($this->storage->getFileByPath($file)->getNamespaces());
    }

    /**
     * @param Structures\FileNamespace[] $namespaces
     *
     * @return Namespace_[]
     */
    private function mapNamespaces(array $namespaces): array
    {
        $result = [];

        foreach ($namespaces as $namespace) {
            $result[] = $this->mapNamespace($namespace);
        }

        return $result;
    }

    /**
     * @param Structures\FileNamespace $namespace
     *
     * @return Namespace_
     */
    private function mapNamespace(Structures\FileNamespace $namespace): Namespace_
    {
        $range = new Range(
            new Position($namespace->getStartLine(), 0),
            new Position($namespace->getEndLine() + 1, 0)
        );

        $imports = $this->mapImports($namespace->getImports());

        return new Namespace_($namespace->getName(), $imports, $range);
    }

    /**
     * @param Structures\FileNamespaceImport[] $imports
     *
     * @return Import[]
     */
    private function mapImports(array $imports): array
    {
        return array_map(function (Structures\FileNamespaceImport $import): Import {
            return $this->mapImport($import);
        }, $imports);
    }

    /**
     * @param Structures\FileNamespaceImport $import
     *
     * @return Import
     */
    private function mapImport(Structures\FileNamespaceImport $import): Import
    {
        return new Import(
            $import->getName(),
            $import->getAlias(),
            $import->getKind(),
            new Position($import->getLine(), 0)
        );
    }
}
