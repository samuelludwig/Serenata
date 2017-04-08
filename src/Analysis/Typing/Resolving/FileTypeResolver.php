<?php

namespace PhpIntegrator\Analysis\Typing\Resolving;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

/**
 * Resolves local types to their FQCN for a file.
 *
 * This is a convenience layer on top of {@see TypeResolver} that accepts a list of namespaces and imports (use
 * statements) for a file and automatically selects the data relevant at the requested line from the list to feed to
 * the underlying resolver.
 */
class FileTypeResolver implements FileTypeResolverInterface
{
    /**
     * @var FileLineNamespaceDeterminer
     */
    private $fileLineNamespaceDeterminer;

    /**
     * @var array
     */
    private $imports;

    /**
     * @var TypeResolverInterface
     */
    private $typeResolver;

    /**
     * @param TypeResolverInterface       $typeResolver
     * @param FileLineNamespaceDeterminer $fileLineNamespaceDeterminer
     * @param array {
     *     @var string $name
     *     @var string $alias
     *     @var string $kind
     *     @var int    $line
     * } $imports
     */
    public function __construct(
        TypeResolverInterface $typeResolver,
        FileLineNamespaceDeterminer $fileLineNamespaceDeterminer,
        array $imports
    ) {
        $this->typeResolver = $typeResolver;
        $this->fileLineNamespaceDeterminer = $fileLineNamespaceDeterminer;
        $this->imports = $imports;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $name, int $line, string $kind = UseStatementKind::TYPE_CLASSLIKE): ?string
    {
        return $this->typeResolver->resolve(
            $name,
            $this->fileLineNamespaceDeterminer->determine($line)->getName(),
            $this->getRelevantUseStatementsForLine($line),
            $kind
        );
    }

    /**
     * @param int $line
     *
     * @return array
     */
    protected function getRelevantUseStatementsForLine(int $line): array
    {
        $namespace = $this->fileLineNamespaceDeterminer->determine($line);

        $relevantImports = [];

        foreach ($this->imports as $import) {
            if ($import['line'] <= $line && $namespace->containsLine($import['line'])) {
                $relevantImports[] = $import;
            }
        }

        return $relevantImports;
    }
}
