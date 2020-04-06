<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;

use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

/**
 * Type deducer that can deduce the type of a {@see Node\Scalar\String_} node.
 */
final class StringNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Scalar\String_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        return new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::STRING_);
    }
}
