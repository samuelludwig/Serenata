<?php

namespace PhpIntegrator\Analysis;

use PhpParser\Node;

/**
 * Result originating from a {@see NodeAtOffsetLocatorInterface}.
 */
class NodeAtOffsetLocatorResult
{
    /**
     * @var Node|null
     */
    private $node;

    /**
     * @var Node|null
     */
    private $nearestInterestingNode;

    /**
     * @param Node|null $node
     * @param Node|null $nearestInterestingNode
     */
    public function __construct(?Node $node, ?Node $nearestInterestingNode)
    {
        $this->node = $node;
        $this->nearestInterestingNode = $nearestInterestingNode;
    }

    /**
     * @return Node|null
     */
    public function getNode(): ?Node
    {
        return $this->node;
    }

    /**
     * @return Node|null
     */
    public function getNearestInterestingNode(): ?Node
    {
        return $this->nearestInterestingNode;
    }
}
