<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Common\Range;
use PhpIntegrator\Common\Position;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\NameQualificationUtilities\Import;
use PhpIntegrator\NameQualificationUtilities\Namespace_;
use PhpIntegrator\NameQualificationUtilities\FileNamespaceProviderInterface;

use PhpIntegrator\Utility\NamespaceData;

/**
 * Provides a list of namespaces and imports for a file based on data provided by a database.
 */
class DatabaseFileNamespaceProvider implements FileNamespaceProviderInterface
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
    public function provide(string $file): array
    {
        $rawImports = $this->indexDatabase->getUseStatementsForFile($file);

        $imports = $this->mapRawImports($rawImports);

        $rawNamespaces = $this->indexDatabase->getNamespacesForFile($file);

        return $this->mapRawNamespaces($rawNamespaces, $imports);
    }

    /**
     * @param array[] $rawImports
     *
     * @return Import[]
     */
    protected function mapRawImports(array $rawImports): array
    {
        return array_map(function (array $rawImport): Import {
            return $this->mapRawImport($rawImport);
        }, $rawImports);
    }

    /**
     * @param array $rawImport
     *
     * @return Import
     */
    protected function mapRawImport(array $rawImport): Import
    {
        return new Import(
            $rawImport['name'],
            $rawImport['alias'],
            $rawImport['kind'],
            new Position($rawImport['line'], 0)
        );
    }

    /**
     * @param array[]  $rawNamespaces
     * @param Import[] $imports
     *
     * @return Namespace_[]
     */
    protected function mapRawNamespaces(array $rawNamespaces, array $imports): array
    {
        $namespaces = [];

        foreach ($rawNamespaces as $rawNamespace) {
            $namespaces[] = $this->mapRawNamespace($rawNamespace, $imports);
        }

        return $namespaces;
    }

    /**
     * @param NamespaceData $rawNamespace
     * @param Import[]      $imports
     *
     * @return Namespace_
     */
    protected function mapRawNamespace(NamespaceData $rawNamespace, array $imports): Namespace_
    {
        $range = new Range(
            new Position($rawNamespace->getStartLine(), 0),
            new Position($rawNamespace->getEndLine(), 0)
        );

        $relevantImports = array_filter($imports, function (Import $import) use ($range) {
            return $range->contains($import->getAppliesAfter());
        });

        return new Namespace_($rawNamespace->getName(), $relevantImports, $range);
    }
}
