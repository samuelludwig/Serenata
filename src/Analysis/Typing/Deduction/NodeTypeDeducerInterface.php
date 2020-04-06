<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Interface for classes that can deduce the type of a node.
 */
interface NodeTypeDeducerInterface
{
    /**
     * @param TypeDeductionContext $context
     *
     * @throws TypeDeductionException
     *
     * @return TypeNode
     */
    public function deduce(TypeDeductionContext $context): TypeNode;
}
