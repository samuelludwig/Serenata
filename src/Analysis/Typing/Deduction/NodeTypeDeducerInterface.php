<?php

namespace Serenata\Analysis\Typing\Deduction;

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
     * @return string[]
     */
    public function deduce(TypeDeductionContext $context): array;
}
