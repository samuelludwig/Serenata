<?php

namespace PhpIntegrator\Indexing\Visiting;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeTraverser;

/**
 * Visitor that causes the body of {@see Node\Stmt\ClassLike} nodes to be skipped.
 *
 * This can be used to improve performance or to simplify logic of other visitors.
 */
class ClassLikeBodySkippingVisitor implements NodeVisitor
{
    /**
     * @inheritDoc
     */
    public function beforeTraverse(array $nodes)
    {
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
    }

    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
    }

    /**
     * @inheritDoc
     */
    public function afterTraverse(array $nodes)
    {
    }
}
