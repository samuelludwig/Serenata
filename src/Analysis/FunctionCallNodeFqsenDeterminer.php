<?php

namespace PhpIntegrator\Analysis;

use LogicException;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;

/**
 * Determines the FQSEN of a function used in a function call node.
 */
class FunctionCallNodeFqsenDeterminer
{
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
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @return string
     */
    public function determine(Node\Expr\FuncCall $node): string
    {
        if (!$node->name instanceof Node\Name) {
            throw new LogicException('Determining the FQSEN of dynamic function calls is not supported');
        }

        $resolvedName = $node->name->getAttribute('resolvedName');

        if ($resolvedName === null) {
            throw new LogicException('Resolved name must be attached to node in order to determine FQSEN');
        }

        $name = NodeHelpers::fetchClassName($resolvedName);

        // False must be used rather than null as the namespace can actually be null.
        $namespaceNode = $node->getAttribute('namespace', false);

        if ($namespaceNode === false) {
            throw new LogicException('Namespace must be attached to node in order to determine FQSEN');
        }

        $namespace = null;

        if ($namespaceNode !== null) {
            $namespace = NodeHelpers::fetchClassName($namespaceNode);
        }

        if ($node->name->isFullyQualified()) {
            return NodeHelpers::fetchClassName($node->name);
        } elseif ($node->name->isQualified()) {
            return '\\' . $namespace . '\\' . $name;
        }

        // Unqualified global function calls, such as "array_walk", could refer to "array_walk" in the current
        // namespace (e.g. "\A\array_walk") or, if not present in the current namespace, the root namespace
        // (e.g. "\array_walk").
        $fqcnForCurrentNamespace = '\\' . $namespace . '\\' . $name;

        if ($this->globalFunctionExistanceChecker->doesGlobalFunctionExist($fqcnForCurrentNamespace)) {
            return $fqcnForCurrentNamespace;
        }

        return '\\' . $name;
    }
}
