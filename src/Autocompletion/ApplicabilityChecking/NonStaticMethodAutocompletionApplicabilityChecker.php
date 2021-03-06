<?php

namespace Serenata\Autocompletion\ApplicabilityChecking;

use PhpParser\Node;

use Serenata\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if non-static method autocompletion applies for a specific node.
 */
final class NonStaticMethodAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
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
        } elseif ($node instanceof Node\Expr\StaticCall &&
            !$node->name instanceof Node\VarLikeIdentifier &&
            !$node->name instanceof Node\Expr &&
            $node->class instanceof Node\Name &&
            in_array($node->class->toString(), ['self', 'parent'], true)
        ) {
            return true;
        } elseif ($node instanceof Node\Expr\StaticPropertyFetch) {
            /** @var Node $name To fix PHPStan error, because this can also be an error node. */
            $name = $node->name;

            if (!$name instanceof Node\VarLikeIdentifier &&
                !$name instanceof Node\Expr &&
                $node->class instanceof Node\Name &&
                in_array($node->class->toString(), ['self', 'parent'], true)) {
                return true;
            }
        } elseif ($node instanceof Node\Expr\ClassConstFetch &&
            $node->class instanceof Node\Name &&
            in_array($node->class->toString(), ['self', 'parent'], true)
        ) {
            return true;
        }

        return $node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\PropertyFetch;
    }
}
