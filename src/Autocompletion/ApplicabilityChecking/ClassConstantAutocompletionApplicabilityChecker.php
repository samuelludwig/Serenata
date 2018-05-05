<?php

namespace Serenata\Autocompletion\ApplicabilityChecking;

use PhpParser\Node;

use Serenata\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if class constant autocompletion applies for a specific node.
 */
final class ClassConstantAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
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
            ($node instanceof Node\Expr\StaticCall && !$node->name instanceof Node\VarLikeIdentifier) ||
            $node instanceof Node\Expr\ClassConstFetch;
    }
}
