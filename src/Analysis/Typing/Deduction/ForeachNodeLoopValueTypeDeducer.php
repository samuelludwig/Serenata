<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Analysis\Typing\TypeAnalyzer;

/**
 * Type deducer that can deduce the type of the loop value of a {@see Node\Stmt\Foreach_} node.
 */
final class ForeachNodeLoopValueTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @param NodeTypeDeducerInterface $nodeTypeDeducer
     * @param TypeAnalyzer             $typeAnalyzer
     */
    public function __construct(NodeTypeDeducerInterface $nodeTypeDeducer, TypeAnalyzer $typeAnalyzer)
    {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->typeAnalyzer = $typeAnalyzer;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Stmt\Foreach_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $types = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->expr,
            $context->getTextDocumentItem()
        ));

        foreach ($types as $type) {
            if ($this->typeAnalyzer->isArraySyntaxTypeHint($type)) {
                return [$this->typeAnalyzer->getValueTypeFromArraySyntaxTypeHint($type)];
            }
        }

        return [];
    }
}
