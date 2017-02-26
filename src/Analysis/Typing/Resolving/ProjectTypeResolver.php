<?php

namespace PhpIntegrator\Analysis\Typing\Resolving;

use LogicException;

use PhpIntegrator\Analysis\GlobalFunctionExistenceCheckerInterface;
use PhpIntegrator\Analysis\GlobalConstantExistenceCheckerInterface;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

/**
 * Resolves local types to their FQCN for a file, utilizing information about a project to increase accuracy.
 *
 * This class decorates a {@see FileTypeResolverInterface} to improve type resolution accuracy by resolving unqualified
 * constant and function names. This can't be performed by the file type resolver as it has no knowledge of what
 * constants and functions exist in a project.
 */
class ProjectTypeResolver implements FileTypeResolverInterface
{
    /**
     * @var FileTypeResolverInterface
     */
    protected $typeResolver;

    /**
     * @var GlobalConstantExistenceCheckerInterface
     */
    protected $globalConstantExistenceChecker;

    /**
     * @var GlobalFunctionExistenceCheckerInterface
     */
    protected $globalFunctionExistenceChecker;

    /**
     * @var array
     */
    protected $namespaces;

    /**
     * @param FileTypeResolverInterface               $typeResolver
     * @param GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker
     * @param GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker
     * @param array {
     *     @var string   $name
     *     @var int      $startLine
     *     @var int|null $endLine
     * } $namespaces
     */
    public function __construct(
        FileTypeResolverInterface $typeResolver,
        GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker,
        GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker,
        array $namespaces
    ) {
        $this->typeResolver = $typeResolver;
        $this->globalConstantExistenceChecker = $globalConstantExistenceChecker;
        $this->globalFunctionExistenceChecker = $globalFunctionExistenceChecker;
        $this->namespaces = $namespaces;
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
        try {
            return $this->typeResolver->resolve($name, $line, $kind);
        } catch (TypeResolutionImpossibleException $e) {
            $namespace = $this->getRelevantNamespaceForLine($line);

            if ($namespace) {
                $namespacedName = '\\' . $namespace . '\\' . $name;

                if ($kind === UseStatementKind::TYPE_CONSTANT) {
                    if ($this->globalConstantExistenceChecker->doesGlobalConstantExist($namespacedName)) {
                        return $namespacedName;
                    }
                } elseif ($kind === UseStatementKind::TYPE_FUNCTION) {
                    if ($this->globalFunctionExistenceChecker->doesGlobalFunctionExist($namespacedName)) {
                        return $namespacedName;
                    }
                }
            }

            // Not in a namespace or the element was not found relative to the active namespace. Either way, the
            // element is absolute (i.e. relative to the "root" namespace).
            return '\\' . $name;
        }

        throw new LogicException('Should never be reached');
    }

    /**
     * @param int $line
     *
     * @return string|null
     */
    protected function getRelevantNamespaceForLine(int $line): ?string
    {
        foreach ($this->namespaces as $namespace) {
            if ($this->lineLiesWithinNamespaceRange($line, $namespace)) {
                return $namespace['name'];
            }
        }

        return null;
    }

    /**
     * @param int   $line
     * @param array $namespace
     *
     * @return bool
     */
    protected function lineLiesWithinNamespaceRange(int $line, array $namespace): bool
    {
        return (
            $line >= $namespace['startLine'] &&
            ($line <= $namespace['endLine'] || $namespace['endLine'] === null)
        );
    }
}
