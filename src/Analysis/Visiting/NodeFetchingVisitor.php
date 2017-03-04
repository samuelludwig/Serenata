<?php

namespace PhpIntegrator\Analysis\Visiting;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that retrieves the node at a specific location.
 */
class NodeFetchingVisitor extends NodeVisitorAbstract
{
    /**
     * @var int
     */
    protected $position;

    /**
     * @var Node
     */
    protected $matchingNode;

    /**
     * @var Node
     */
    protected $mostInterestingNode;

    /**
     * Constructor.
     *
     * @param int $position
     */
    public function __construct(int $position)
    {
        $this->position = $position;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        $endFilePos = $node->getAttribute('endFilePos');
        $startFilePos = $node->getAttribute('startFilePos');

        if ($startFilePos > $this->position || $endFilePos < $this->position) {
            return;
        } elseif ($node instanceof Node\FunctionLike) {
            // The range of function and method definitions extends over their entire body. We don't want to see
            // these as nodes at that location. This isn't a great solution, but will at least confine the range to
            // everything before the first statements. Will hopefully be solved with the following ticket:
            //
            //   https://github.com/nikic/PHP-Parser/issues/322 .
            if (!empty($node->getStmts()) && $node->getStmts()[0]->getAttribute('startFilePos') < $this->position) {
                return;
            } elseif (!empty($node->getParams()) && $node->getParams()[0]->getAttribute('startFilePos') < $this->position) {
                return;
            } elseif (
                !empty($node->getReturnType()) &&
                $node->getReturnType() instanceof Node &&
                $node->getReturnType()->getAttribute('startFilePos') < $this->position
            ) {
                return;
            }
        }

        $this->matchingNode = $node;

        if (!$node instanceof Node\Name) {
            $this->mostInterestingNode = $node;
        }
    }

    /**
     * @return Node|null
     */
    public function getNode(): ?Node
    {
        return $this->matchingNode;
    }

    /**
     * Returns the same as {@see getNode}, or the nearest node that is more interesting.
     *
     * "More interesting" is defined in terms of what is more useful. {@see getNode} may return the name node inside a
     * function call, whilst this method will return the function call instead.
     *
     * @return Node|null
     */
    public function getNearestInterestingNode(): ?Node
    {
        return $this->mostInterestingNode;
    }
}
