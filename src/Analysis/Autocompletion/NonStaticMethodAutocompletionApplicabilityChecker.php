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
        } elseif ($node instanceof Node\Name || $node instanceof Node\Identifier) {
            return $this->doesApplyTo($node->getAttribute('parent'));
        } elseif ($node instanceof Node\Expr\Error) {
            $parent = $node->getAttribute('parent', false);

            return $parent !== false ? $this->doesApplyTo($parent) : false;
        } elseif ($node instanceof Node\Expr\StaticCall &&
            $node->class instanceof Node\Name &&
            in_array($node->class->toString(), ['self', 'parent'], true)
        ) {
            return true;
        } elseif ($node instanceof Node\Expr\StaticPropertyFetch &&
            $node->class instanceof Node\Name &&
            in_array($node->class->toString(), ['self', 'parent'], true)
        ) {
            return true;
        } elseif ($node instanceof Node\Expr\ClassConstFetch &&
            $node->class instanceof Node\Name &&
            in_array($node->class->toString(), ['self', 'parent'], true)
        ) {
            return true;
        }

        return $node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\PropertyFetch;
    }
}
