<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpParser\Node;

/**
 * Checks if static method autocompletion applies for a specific node.
 */
final class StaticMethodAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
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
        } elseif ($node instanceof Node\Expr\StaticCall &&
            !$node->name instanceof Node\VarLikeIdentifier &&
            !$node->name instanceof Node\Expr
        ) {
            return true;
        } elseif ($node instanceof Node\Expr\StaticPropertyFetch &&
            !$node->name instanceof Node\VarLikeIdentifier &&
            !$node->name instanceof Node\Expr
        ) {
            return true;
        }

        return $node instanceof Node\Expr\ClassConstFetch;
    }
}
