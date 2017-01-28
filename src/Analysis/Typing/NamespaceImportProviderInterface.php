<?php

namespace PhpIntegrator\Analysis\Typing;

/**
 * Interfaces for classes that can provide information about the namespaces and imports (use statements) in a file.
 */
interface NamespaceImportProviderInterface
{
    /**
     * @param string $filePath
     *
     * @return array {
     *     @var string   $name
     *     @var int      $startLine
     *     @var int|null $endLine
     * }
     */
    public function getNamespacesForFile(string $filePath): array;

    /**
     * @param string $filePath
     *
     * @return array {
     *     @var string $fqcn
     *     @var string $alias
     *     @var string $kind
     *     @var int    $line
     * }
     */
    public function getUseStatementsForFile(string $filePath): array;

    /**
     * @return array {
     *     @var string $name
     * }
     */
    public function getNamespaces(): array;
}
