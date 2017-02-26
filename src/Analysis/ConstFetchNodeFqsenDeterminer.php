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
     * @var GlobalConstantExistenceCheckerInterface
     */
    protected $globalConstantExistenceChecker;

    /**
     * @param GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker
     */
    public function __construct(GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker)
    {
        $this->globalConstantExistenceChecker = $globalConstantExistenceChecker;
    }

    /**
     * @param Node\Expr\ConstFetch $node
     *
     * @return string
     */
    public function determine(Node\Expr\ConstFetch $node): string
    {
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

        if ($this->globalConstantExistenceChecker->exists($fqcnForCurrentNamespace)) {
            return $fqcnForCurrentNamespace;
        }

        return '\\' . $node->name->toString();
    }
}
