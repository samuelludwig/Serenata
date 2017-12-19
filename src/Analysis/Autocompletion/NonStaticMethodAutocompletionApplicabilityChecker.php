<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpParser\Node;

/**
 * Checks if non-static method autocompletion applies for a specific node.
 */
final class NonStaticMethodAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
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
        } elseif ($node instanceof Node\Expr\StaticCall &&
            $node->class instanceof Node\Name &&
            $node->class->toString() === 'parent'
        ) {
            return true;
        } elseif ($node instanceof Node\Expr\StaticPropertyFetch &&
            $node->class instanceof Node\Name &&
            $node->class->toString() === 'parent'
        ) {
            return true;
        } elseif ($node instanceof Node\Expr\ClassConstFetch &&
            $node->class instanceof Node\Name &&
            $node->class->toString() === 'parent'
        ) {
            return true;
        }

        return $node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\PropertyFetch;
    }
}
