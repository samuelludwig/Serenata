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
    private $typeResolver;

    /**
     * @var GlobalConstantExistenceCheckerInterface
     */
    private $globalConstantExistenceChecker;

    /**
     * @var GlobalFunctionExistenceCheckerInterface
     */
    private $globalFunctionExistenceChecker;

    /**
     * @var FileLineNamespaceDeterminer
     */
    private $fileLineNamespaceDeterminer;

    /**
     * @param FileTypeResolverInterface               $typeResolver
     * @param GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker
     * @param GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker
     * @param FileLineNamespaceDeterminer             $fileLineNamespaceDeterminer
     */
    public function __construct(
        FileTypeResolverInterface $typeResolver,
        GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker,
        GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker,
        FileLineNamespaceDeterminer $fileLineNamespaceDeterminer
    ) {
        $this->typeResolver = $typeResolver;
        $this->globalConstantExistenceChecker = $globalConstantExistenceChecker;
        $this->globalFunctionExistenceChecker = $globalFunctionExistenceChecker;
        $this->fileLineNamespaceDeterminer = $fileLineNamespaceDeterminer;
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
            $namespacedName = '\\' . $name;

            $namespace = $this->fileLineNamespaceDeterminer->determine($line);

            if ($namespace->getName() !== null) {
                $namespacedName = '\\' . $namespace->getName() . $namespacedName;
            }

            if ($kind === UseStatementKind::TYPE_CONSTANT) {
                if ($this->globalConstantExistenceChecker->exists($namespacedName)) {
                    return $namespacedName;
                }
            } elseif ($kind === UseStatementKind::TYPE_FUNCTION) {
                if ($this->globalFunctionExistenceChecker->exists($namespacedName)) {
                    return $namespacedName;
                }
            }

            // Not in a namespace or the element was not found relative to the active namespace. Either way, the
            // element is absolute (i.e. relative to the "root" namespace).
            return '\\' . $name;
        }

        throw new LogicException('Should never be reached');
    }
}
