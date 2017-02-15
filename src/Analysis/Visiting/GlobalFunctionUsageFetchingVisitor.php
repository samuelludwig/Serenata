<?php

namespace PhpIntegrator\Analysis\Visiting;

use PhpParser\Node;

/**
 * Node visitor that fetches usages of (global) functions.
 */
class GlobalFunctionUsageFetchingVisitor extends AbstractNameResolvingVisitor
{
    /**
     * @var Node\Expr\FuncCall[]
     */
    protected $globalFunctionCallList = [];

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if (!$node instanceof Node\Expr\FuncCall || !$node->name instanceof Node\Name) {
            return;
        }

        $this->globalFunctionCallList[] = $node;
    }

    /**
     * @return Node\Expr\FuncCall[]
     */
    public function getGlobalFunctionCallList(): array
    {
        return $this->globalFunctionCallList;
    }
}
