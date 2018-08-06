<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Scalar\String_} node.
 */
final class StringNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Scalar\String_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        return ['string'];
    }
}
