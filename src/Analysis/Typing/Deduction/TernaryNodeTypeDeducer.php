<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

use PhpParser\Node;

use Serenata\Parsing\ToplevelTypeExtractorInterface;

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
     * @var ToplevelTypeExtractorInterface
     */
    private $toplevelTypeExtractor;

    /**
     * @param NodeTypeDeducerInterface       $nodeTypeDeducer
     * @param ToplevelTypeExtractorInterface $toplevelTypeExtractor
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ToplevelTypeExtractorInterface $toplevelTypeExtractor
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->toplevelTypeExtractor = $toplevelTypeExtractor;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Expr\Ternary) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $firstOperandType = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->if !== null ? $context->getNode()->if : $context->getNode()->cond,
            $context->getTextDocumentItem()
        ));

        $secondOperandType = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->else,
            $context->getTextDocumentItem()
        ));

        return $this->consolidateDuplicateTypes($firstOperandType, $secondOperandType);
    }

    /**
     * @param TypeNode $firstOperandType
     * @param TypeNode $secondOperandType
     *
     * @return TypeNode
     */
    private function consolidateDuplicateTypes(TypeNode $firstOperandType, TypeNode $secondOperandType): TypeNode
    {
        $firstOperandTypes = $this->toplevelTypeExtractor->extract($firstOperandType);
        $secondOperandTypes = $this->toplevelTypeExtractor->extract($secondOperandType);

        $allTypes = array_merge($firstOperandTypes, $secondOperandTypes);
        $uniqueTypeStrings = array_unique(array_merge($firstOperandTypes, $secondOperandTypes));

        $uniqueTypes = [];

        foreach (array_keys($uniqueTypeStrings) as $key) {
            $uniqueTypes[] = $allTypes[$key];
        }

        return new UnionTypeNode($uniqueTypes);
    }
}
