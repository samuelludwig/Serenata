<?php

namespace PhpIntegrator\Autocompletion;

use PhpParser\Node;

use PhpIntegrator\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if static property autocompletion applies for a specific node.
 */
final class StaticPropertyAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function doesApplyToPrefix(string $prefix): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function doesApplyTo(NodeAtOffsetLocatorResult $nodeAtOffsetLocatorResult): bool
    {
        if ($nodeAtOffsetLocatorResult->getNode() === null) {
            return false;
        }

        return $this->doesApplyToNode($nodeAtOffsetLocatorResult->getNode());
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    private function doesApplyToNode(Node $node): bool
    {
        if ($node instanceof Node\Stmt\Expression) {
            return $this->doesApplyToNode($node->expr);
        } elseif ($node instanceof Node\Name || $node instanceof Node\Identifier) {
            return $this->doesApplyToNode($node->getAttribute('parent'));
        } elseif ($node instanceof Node\Expr\Error) {
            $parent = $node->getAttribute('parent', false);

            return $parent !== false ? $this->doesApplyToNode($parent) : false;
        }

        return
            $node instanceof Node\Expr\StaticCall ||
            $node instanceof Node\Expr\StaticPropertyFetch ||
            $node instanceof Node\Expr\ClassConstFetch;
    }
}
