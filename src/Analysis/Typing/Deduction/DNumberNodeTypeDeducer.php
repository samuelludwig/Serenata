<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Scalar\DNumber} node.
 */
final class DNumberNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Scalar\DNumber) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        return ['float'];
    }
}
