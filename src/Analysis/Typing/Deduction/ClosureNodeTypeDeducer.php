<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Closure} node.
 */
final class ClosureNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Expr\Closure &&
            !$context->getNode() instanceof Node\Expr\ArrowFunction) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        return new IdentifierTypeNode('\Closure');
    }
}
