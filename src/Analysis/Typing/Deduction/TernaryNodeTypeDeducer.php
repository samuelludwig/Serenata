<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Ternary} node.
 */
final class TernaryNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @param NodeTypeDeducerInterface $nodeTypeDeducer
     */
    public function __construct(NodeTypeDeducerInterface $nodeTypeDeducer)
    {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Expr\Ternary) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $firstOperandTypes = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->if ?: $context->getNode()->cond,
            $context->getTextDocumentItem()
        ));

        $secondOperandTypes = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->else,
            $context->getTextDocumentItem()
        ));

        return array_unique(array_merge($firstOperandTypes, $secondOperandTypes));
    }
}
