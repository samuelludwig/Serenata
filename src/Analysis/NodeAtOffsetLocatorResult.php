<?php

namespace Serenata\Analysis;

use PhpParser\Node;
use PhpParser\Comment;

/**
 * Result originating from a {@see NodeAtOffsetLocatorInterface}.
 */
final class NodeAtOffsetLocatorResult
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
     * @var Comment|null
     */
    private $comment;

    /**
     * @param Node|null    $node
     * @param Node|null    $nearestInterestingNode
     * @param Comment|null $comment
     */
    public function __construct(?Node $node, ?Node $nearestInterestingNode, ?Comment $comment)
    {
        $this->node = $node;
        $this->nearestInterestingNode = $nearestInterestingNode;
        $this->comment = $comment;
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

    /**
     * @return Comment|null
     */
    public function getComment(): ?Comment
    {
        return $this->comment;
    }
}
