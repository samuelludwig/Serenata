<?php

namespace PhpIntegrator\Autocompletion\ApplicabilityChecking;

use PhpParser\Node;

use PhpIntegrator\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if constant autocompletion applies for a specific node.
 */
final class ConstantAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
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
        if ($node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\UseUse) {
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
        } elseif ($node instanceof Node\Expr\Variable) {
            return false;
        } elseif ($node instanceof Node\Stmt\Namespace_) {
            return false;
        } elseif ($node instanceof Node\Stmt\Expression) {
            return $this->doesApplyToNode($node->expr);
        } elseif ($node instanceof Node\Expr\Error) {
            $parent = $node->getAttribute('parent', false);

            return $parent !== false ? $this->doesApplyToNode($parent) : false;
        } elseif ($node instanceof Node\Name || $node instanceof Node\Identifier) {
            return $this->doesApplyToNode($node->getAttribute('parent'));
        }

        return true;
    }
}