<?php

namespace PhpIntegrator\Autocompletion\ApplicabilityChecking;

use PhpParser\Node;

use PhpIntegrator\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if namespace autocompletion applies for a specific node.
 */
final class NamespaceAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function doesApplyToPrefix(string $prefix): bool
    {
        // Prevent trigger happy suggestions when user hasn't even actually typed anything, resulting in some editors
        // immediately and unwantedly confirming a suggestion when the user attempted to create a newline.
        return $prefix !== '';
    }

    /**
     * @inheritDoc
     */
    public function doesApplyTo(NodeAtOffsetLocatorResult $nodeAtOffsetLocatorResult): bool
    {
        if ($nodeAtOffsetLocatorResult->getComment() !== null) {
            return false;
        } elseif ($nodeAtOffsetLocatorResult->getNode() === null) {
            return true;
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
            $node instanceof Node\Stmt\Use_ ||
            $node instanceof Node\Stmt\UseUse ||
            $node instanceof Node\Stmt\Namespace_;
    }
}
