<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpParser\Node;

/**
 * Checks if non-static property autocompletion applies for a specific node.
 */
final class NonStaticPropertyAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function doesApplyOutsideNodes(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function doesApplyTo(Node $node): bool
    {
        if ($node instanceof Node\Stmt\Expression) {
            return $this->doesApplyTo($node->expr);
        } elseif ($node instanceof Node\Name || $node instanceof Node\Identifier) {
            return $this->doesApplyTo($node->getAttribute('parent'));
        } elseif ($node instanceof Node\Expr\Error) {
            $parent = $node->getAttribute('parent', false);

            return $parent !== false ? $this->doesApplyTo($parent) : false;
        }

        return $node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\PropertyFetch;
    }
}
