<?php

namespace PhpIntegrator\Analysis\Linting;

use PhpIntegrator\Analysis\GlobalFunctionExistanceCheckerInterface;

use PhpIntegrator\Analysis\Visiting\NamespaceAttachingVisitor;
use PhpIntegrator\Analysis\Visiting\GlobalFunctionUsageFetchingVisitor;

use PhpIntegrator\Utility\NodeHelpers;

/**
 * Looks for unknown global function names (i.e. used during calls).
 */
class UnknownGlobalFunctionAnalyzer implements AnalyzerInterface
{
    /**
     * @var NamespaceAttachingVisitor
     */
    protected $namespaceAttachingVisitor;

    /**
     * @var GlobalFunctionUsageFetchingVisitor
     */
    protected $globalFunctionUsageFetchingVisitor;

    /**
     * @var GlobalFunctionExistanceCheckerInterface
     */
    protected $globalFunctionExistanceChecker;

    /**
     * @param GlobalFunctionExistanceCheckerInterface $globalFunctionExistanceChecker
     */
    public function __construct(GlobalFunctionExistanceCheckerInterface $globalFunctionExistanceChecker)
    {
        $this->globalFunctionExistanceChecker = $globalFunctionExistanceChecker;

        $this->namespaceAttachingVisitor = new NamespaceAttachingVisitor();
        $this->globalFunctionUsageFetchingVisitor = new GlobalFunctionUsageFetchingVisitor();
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->namespaceAttachingVisitor,
            $this->globalFunctionUsageFetchingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOutput(): array
    {
        $globalFunctions = $this->globalFunctionUsageFetchingVisitor->getGlobalFunctionCallList();

        $unknownGlobalFunctions = [];

        foreach ($globalFunctions as $node) {
            $name = NodeHelpers::fetchClassName($node->name->getAttribute('resolvedName'));

            $namespaceNode = $node->getAttribute('namespace');
            $namespace = null;

            if ($namespaceNode !== null) {
                $namespace = NodeHelpers::fetchClassName($namespaceNode);
            }

            if ($this->globalFunctionExistanceChecker->doesGlobalFunctionExist($name)) {
                continue;
            } elseif ($node->name->isUnqualified()) {
                // Unqualified global function calls, such as "array_walk", could refer to "array_walk" in the current
                // namespace (e.g. "\A\array_walk") or, if not present in the current namespace, the root namespace
                // (e.g. "\array_walk").
                $fqcnForCurrentNamespace = '\\' . $namespace . '\\' . $name;

                if ($this->globalFunctionExistanceChecker->doesGlobalFunctionExist($fqcnForCurrentNamespace)) {
                    continue;
                }

                $fqcnForRootNamespace = '\\' . $name;

                if ($this->globalFunctionExistanceChecker->doesGlobalFunctionExist($fqcnForRootNamespace)) {
                    continue;
                }
            }

            $unknownGlobalFunctions[] = [
                'name'  => $name,
                'start' => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos')   : null,
                'end'   => $node->getAttribute('endFilePos')   ? $node->getAttribute('endFilePos') + 1 : null
            ];
        }

        return $unknownGlobalFunctions;
    }
}
