<?php

namespace PhpIntegrator\Analysis\Typing\Localization;

use PhpIntegrator\Analysis\Typing\Resolving\FileLineNamespaceDeterminer;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

/**
 * Resolves FQCN's back to local types based on use statements and the namespace.
 *
 * This is a convenience layer on top of {@see TypeLocalizer} that accepts a list of namespaces and imports (use
 * statements) for a file and automatically selects the data relevant at the requested line from the list to feed to
 * the underlying localizer.
 */
class FileTypeLocalizer
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
     * @var TypeLocalizer
     */
    private $typeLocalizer;

    /**
     * @param TypeLocalizer $typeLocalizer
     * @param FileLineNamespaceDeterminer $fileLineNamespaceDeterminer
     * @param array {
     *     @var string $fqcn
     *     @var string $alias
     *     @var string $kind
     *     @var int    $line
     * } $imports
     */
    public function __construct(
        TypeLocalizer $typeLocalizer,
        FileLineNamespaceDeterminer $fileLineNamespaceDeterminer,
        array $imports
    ) {
        $this->typeLocalizer = $typeLocalizer;
        $this->fileLineNamespaceDeterminer = $fileLineNamespaceDeterminer;
        $this->imports = $imports;
    }

    /**
     * Resolves and determines the FQCN of the specified type.
     *
     * @param string $name
     * @param int    $line
     * @param string $kind
     *
     * @return string|null
     */
    public function resolve(string $name, int $line, string $kind = UseStatementKind::TYPE_CLASSLIKE): ?string
    {
        return $this->typeLocalizer->localize(
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
