<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpParser\Node;

/**
 * Checks if local variable autocompletion applies for a specific node.
 */
final class LocalVariableAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function doesApplyOutsideNodes(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function doesApplyTo(Node $node): bool
    {
        if ($node instanceof Node\Stmt\Expression && $node->expr instanceof Node\Expr\Variable) {
            return false;
        } elseif ($node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\UseUse) {
            return false;
        } elseif ($node instanceof Node\Expr\StaticPropertyFetch) {
            return false;
        } elseif ($node instanceof Node\Expr\StaticCall) {
            return false;
        } elseif ($node instanceof Node\Expr\MethodCall) {
            return false;
        } elseif ($node instanceof Node\Expr\PropertyFetch) {
            return false;
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            return false;
        } elseif ($node instanceof Node\Scalar) {
            return false;
        } elseif ($node instanceof Node\Stmt\ClassLike) {
            return false;
        } elseif ($node instanceof Node\Stmt\Expression) {
            return $this->doesApplyTo($node->expr);
        } /*elseif ($node instanceof Node\Stmt\Function_) {
            return false;
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            return false;
        } elseif ($node instanceof Node\Identifier || $node instanceof Node\Expr\Error) {

        }*/

        return true;
    }
}