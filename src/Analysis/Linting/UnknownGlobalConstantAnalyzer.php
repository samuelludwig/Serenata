<?php

namespace PhpIntegrator\Analysis\Linting;

use PhpIntegrator\Analysis\GlobalConstantExistanceCheckerInterface;

use PhpIntegrator\Analysis\Visiting\GlobalConstantUsageFetchingVisitor;

use PhpIntegrator\Utility\NodeHelpers;

/**
 * Looks for unknown global constant names.
 */
class UnknownGlobalConstantAnalyzer implements AnalyzerInterface
{
    /**
     * @var GlobalConstantExistanceCheckerInterface
     */
    protected $globalConstantExistanceChecker;

    /**
     * @var GlobalConstantUsageFetchingVisitor
     */
    protected $globalConstantUsageFetchingVisitor;

    /**
     * @param GlobalConstantExistanceCheckerInterface $globalConstantExistanceChecker
     */
    public function __construct(GlobalConstantExistanceCheckerInterface $globalConstantExistanceChecker)
    {
        $this->globalConstantExistanceChecker = $globalConstantExistanceChecker;

        $this->globalConstantUsageFetchingVisitor = new GlobalConstantUsageFetchingVisitor();
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->globalConstantUsageFetchingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOutput(): array
    {
        $globalConstants = $this->globalConstantUsageFetchingVisitor->getGlobalConstantList();

        $unknownGlobalConstants = [];

        foreach ($globalConstants as $node) {
            $name = NodeHelpers::fetchClassName($node->name->getAttribute('resolvedName'));

            $namespaceNode = $node->getAttribute('namespace');
            $namespace = null;

            if ($namespaceNode !== null) {
                $namespace = NodeHelpers::fetchClassName($namespaceNode);
            }

            if ($this->globalConstantExistanceChecker->doesGlobalConstantExist($name)) {
                continue;
            } elseif ($node->name->isUnqualified()) {
                // Unqualified global constant calls, such as "PHP_EOL", could refer to "PHP_EOL" in the current
                // namespace (e.g. "\A\PHP_EOL") or, if not present in the current namespace, the root namespace
                // (e.g. "\PHP_EOL").
                $fqcnForCurrentNamespace = '\\' . $namespace . '\\' . $name;

                if ($this->globalConstantExistanceChecker->doesGlobalConstantExist($fqcnForCurrentNamespace)) {
                    continue;
                }

                $fqcnForRootNamespace = '\\' . $name;

                if ($this->globalConstantExistanceChecker->doesGlobalConstantExist($fqcnForRootNamespace)) {
                    continue;
                }
            }

            $unknownGlobalConstants[] = [
                'name'  => $name,
                'start' => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos')   : null,
                'end'   => $node->getAttribute('endFilePos')   ? $node->getAttribute('endFilePos') + 1 : null
            ];;
        }

        return $unknownGlobalConstants;
    }
}
