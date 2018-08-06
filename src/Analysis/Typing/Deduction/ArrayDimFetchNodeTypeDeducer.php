<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Analysis\Typing\TypeAnalyzer;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ArrayDimFetch} node.
 */
final class ArrayDimFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @param TypeAnalyzer             $typeAnalyzer
     * @param NodeTypeDeducerInterface $nodeTypeDeducer
     */
    public function __construct(TypeAnalyzer $typeAnalyzer, NodeTypeDeducerInterface $nodeTypeDeducer)
    {
        $this->typeAnalyzer = $typeAnalyzer;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Expr\ArrayDimFetch) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $types = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->var,
            $context->getTextDocumentItem()
        ));

        $elementTypes = [];

        foreach ($types as $type) {
            if ($type === 'string') {
                $elementTypes[] = 'string';
            } elseif ($this->typeAnalyzer->isArraySyntaxTypeHint($type)) {
                $elementTypes[] = $this->typeAnalyzer->getValueTypeFromArraySyntaxTypeHint($type);
            } else {
                $elementTypes[] = 'mixed';
            }
        }

        return array_unique($elementTypes);
    }
}
