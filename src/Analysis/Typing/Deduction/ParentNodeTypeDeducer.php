<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Parsing;

/**
 * Type deducer that can deduce the type of a {@see Parsing\Node\Keyword\Parent_} node.
 */
final class ParentNodeTypeDeducer extends AbstractNodeTypeDeducer
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
        if (!$context->getNode() instanceof Parsing\Node\Keyword\Parent_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $node = new Node\Name('parent');
        $node->setAttribute('startFilePos', $context->getNode()->getAttribute('startFilePos'));

        return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $node,
            $context->getTextDocumentItem(),
            $context->getPosition()
        ));
    }
}
