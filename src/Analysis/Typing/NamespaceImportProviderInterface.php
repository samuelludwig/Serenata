<?php

namespace Serenata\Analysis\Typing;

use Serenata\Utility\NamespaceData;

/**
 * Interface for classes that can provide information about the namespaces and imports (use statements) in a file.
 */
interface NamespaceImportProviderInterface
{
    /**
     * @param string $filePath
     *
     * @return NamespaceData[]
     */
    public function getNamespacesForFile(string $filePath): array;

    /**
     * @param string $filePath
     *
     * @return array<string,mixed> array {
     *     string $fqcn
     *     string $alias
     *     string $kind
     *     int    $line
     * }
     */
    public function getUseStatementsForFile(string $filePath): array;

    /**
     * @return array<string,mixed> array {
     *     string $name
     * }
     */
    public function getNamespaces(): array;
}
