<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use PhpParser\Node;

/**
 * Interface for factories that create objects implementing {@see NodeTypeDeducerInterface}.
 */
interface NodeTypeDeducerFactoryInterface
{
    /**
     * @param Node $node
     *
     * @throws NoTypeDeducerFoundException when no type deducer can be created for the specified node.
     *
     * @return NodeTypeDeducerInterface
     */
    public function create(Node $node);
}
