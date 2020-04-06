<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\New_} node.
 */
final class NewNodeTypeDeducer extends AbstractNodeTypeDeducer
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
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Expr\New_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->class,
            $context->getTextDocumentItem()
        ));
    }
}
