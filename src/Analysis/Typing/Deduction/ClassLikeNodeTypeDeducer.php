<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Utility\NodeHelpers;

/**
 * Type deducer that can deduce the type of a {@see Node\Stmt\ClassLike} node.
 */
final class ClassLikeNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Stmt\ClassLike) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        } elseif ($context->getNode() instanceof Node\Stmt\Class_ && $context->getNode()->name === null) {
            return [NodeHelpers::getFqcnForAnonymousClassNode(
                $context->getNode(),
                $context->getTextDocumentItem()->getUri()
            )];
        }

        return [(string) $context->getNode()->name];
    }
}
