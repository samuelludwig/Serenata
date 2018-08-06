<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Clone_} node.
 */
final class CloneNodeTypeDeducer extends AbstractNodeTypeDeducer
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
        if (!$context->getNode() instanceof Node\Expr\Clone_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->expr,
            $context->getTextDocumentItem()
        ));
    }
}
