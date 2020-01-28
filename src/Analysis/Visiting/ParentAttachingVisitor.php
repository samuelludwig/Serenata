<?php

namespace Serenata\Analysis\Visiting;

use SplStack;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that attaches the parent of a node to the node proper.
 */
final class ParentAttachingVisitor extends NodeVisitorAbstract
{
    /**
     * @var SplStack
     */
    private $stack;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->stack = new SplStack();
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        $node->setAttribute('parent', $this->stack->isEmpty() ? null : $this->stack->top());

        $this->stack->push($node);

        return null;
    }

    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        parent::leaveNode($node);

        $this->stack->pop();

        return null;
    }
}
