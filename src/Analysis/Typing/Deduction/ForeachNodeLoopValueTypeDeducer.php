<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;

use PhpParser\Node;

use Serenata\Parsing\InvalidTypeNode;
use Serenata\Parsing\ToplevelTypeExtractorInterface;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

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
        if (!$context->getNode() instanceof Node\Stmt\Foreach_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $iteratedExpressionType = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->expr,
            $context->getTextDocumentItem()
        ));

        foreach ($this->toplevelTypeExtractor->extract($iteratedExpressionType) as $type) {
            if ($type instanceof ArrayTypeNode) {
                return $type->type;
            } elseif ($type instanceof GenericTypeNode &&
                in_array($type->type->name, [
                    SpecialDocblockTypeIdentifierLiteral::ARRAY_,
                    SpecialDocblockTypeIdentifierLiteral::ITERABLE_,
                ])
            ) {
                if (isset($type->genericTypes[1])) {
                    return $type->genericTypes[1];
                } elseif (isset($type->genericTypes[0])) {
                    return $type->genericTypes[0];
                }
            }
        }

        return new InvalidTypeNode();
    }
}
