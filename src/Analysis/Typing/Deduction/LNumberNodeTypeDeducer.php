<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Scalar\LNumber} node.
 */
final class LNumberNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Scalar\LNumber) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        return ['int'];
    }
}
