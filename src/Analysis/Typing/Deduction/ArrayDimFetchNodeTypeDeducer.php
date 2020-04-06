<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;

use Serenata\Parsing\TypeNodeUnwrapper;
use Serenata\Parsing\ToplevelTypeExtractorInterface;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ArrayDimFetch} node.
 */
final class ArrayDimFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
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
        if (!$context->getNode() instanceof Node\Expr\ArrayDimFetch) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $type = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->var,
            $context->getTextDocumentItem()
        ));

        $elementTypes = [];

        foreach ($this->toplevelTypeExtractor->extract($type) as $type) {
            if ($type instanceof IdentifierTypeNode && $type->name === SpecialDocblockTypeIdentifierLiteral::STRING_) {
                $elementTypes[] = new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::STRING_);
            } elseif ($type instanceof ArrayTypeNode) {
                $elementTypes[] = $type->type;
            } else {
                // TODO: This could be an object implementing ArrayAccess. Consult the object's interfaces and methods.
                $elementTypes[] = new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::MIXED_);
            }
        }

        return TypeNodeUnwrapper::unwrap(new UnionTypeNode($elementTypes));
    }
}
