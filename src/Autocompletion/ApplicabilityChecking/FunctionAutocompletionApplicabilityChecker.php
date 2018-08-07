<?php

namespace Serenata\Autocompletion\ApplicabilityChecking;

use PhpParser\Node;


use Serenata\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if function autocompletion applies for a specific node.
 */
final class FunctionAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
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
        } elseif ($node instanceof Node\Stmt\Property || $node instanceof Node\Stmt\PropertyProperty) {
            return false;
        } elseif ($node instanceof Node\Const_) {
            return false;
        } elseif ($node instanceof Node\Param) {
            return false;
        } elseif ($node instanceof Node\Expr\New_) {
            return false;
        } elseif ($node instanceof Node\Stmt\TraitUse) {
            return false;
        } elseif ($node instanceof Node\Stmt\TraitUseAdaptation\Alias) {
            return false;
        } elseif ($node instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
            return false;
        } elseif ($node instanceof Node\Stmt\Expression) {
            return $this->doesApplyToNode($node->expr);
        } elseif ($node instanceof Node\Expr\Clone_) {
            return false;
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            $parent = $node->getAttribute('parent', false);

            if ($parent instanceof Node\Expr\Clone_) {
                return false;
            } elseif ($parent instanceof Node\Param && $parent->default === $node) {
                return false;
            }
        } elseif ($node instanceof Node\Expr\Error) {
            $parent = $node->getAttribute('parent', false);

            return $parent !== false ? $this->doesApplyToNode($parent) : false;
        } elseif ($node instanceof Node\Name) {
            return $this->doesApplyToNode($node->getAttribute('parent'));
        } elseif ($node instanceof Node\Identifier) {
            $parent = $node->getAttribute('parent');

            if ($parent instanceof Node\FunctionLike) {
                return false;
            }

            return $this->doesApplyToNode($parent);
        }

        return true;
    }
}
