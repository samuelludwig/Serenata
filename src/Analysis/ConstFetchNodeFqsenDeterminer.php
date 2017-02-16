<?php

namespace PhpIntegrator\Analysis;

use LogicException;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;

/**
 * Determines the FQSEN of a constant used in a const fetch node.
 */
class ConstFetchNodeFqsenDeterminer
{
    /**
     * @var GlobalConstantExistanceCheckerInterface
     */
    protected $globalConstantExistanceChecker;

    /**
     * @param GlobalConstantExistanceCheckerInterface $globalConstantExistanceChecker
     */
    public function __construct(GlobalConstantExistanceCheckerInterface $globalConstantExistanceChecker)
    {
        $this->globalConstantExistanceChecker = $globalConstantExistanceChecker;
    }

    /**
     * @param Node\Expr\ConstFetch $node
     *
     * @return string
     */
    public function determine(Node\Expr\ConstFetch $node): string
    {
        $resolvedName = $node->name->getAttribute('resolvedName');

        if ($resolvedName === null) {
            throw new LogicException('Resolved name must be attached to node in order to determine FQSEN');
        }

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
            return '\\' . $namespace . '\\' . $node->name->toString();
        }

        // Unqualified global function calls, such as "array_walk", could refer to "array_walk" in the current
        // namespace (e.g. "\A\array_walk") or, if not present in the current namespace, the root namespace
        // (e.g. "\array_walk").
        $fqcnForCurrentNamespace = '\\' . $namespace . '\\' . $node->name->toString();

        if ($this->globalConstantExistanceChecker->doesGlobalConstantExist($fqcnForCurrentNamespace)) {
            return $fqcnForCurrentNamespace;
        }

        return '\\' . $node->name->toString();
    }
}
