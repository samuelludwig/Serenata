<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use PhpParser\Node;

use Serenata\Common\Position;

use Serenata\Utility\PositionEncoding;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Assign} node.
 */
final class AssignNodeTypeDeducer extends AbstractNodeTypeDeducer
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
        if (!$context->getNode() instanceof Node\Expr\Assign) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->expr,
            $context->getTextDocumentItem(),
            // Do not use position of expression node to avoid infinite loop in self-assignment statements.
            Position::createFromByteOffset(
                $context->getNode()->getAttribute('startFilePos'),
                $context->getTextDocumentItem()->getText(),
                PositionEncoding::VALUE
            )
        ));
    }
}
