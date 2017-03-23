<?php

namespace PhpIntegrator\Analysis\Visiting;

use PhpParser\Node;

/**
 * Node visitor that fetches usages of (global) constants.
 */
class GlobalConstantUsageFetchingVisitor extends ResolvedNameAttachingVisitor
{
    /**
     * @var Node\Expr\ConstFetch[]
     */
    private $globalConstantList = [];

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if (!$node instanceof Node\Expr\ConstFetch) {
            return;
        }

        if (!$this->isConstantExcluded($node->name->toString())) {
            $this->globalConstantList[] = $node;
        }
    }

   /**
    * @param string $name
    *
    * @return bool
    */
   protected function isConstantExcluded(string $name): bool
   {
       return in_array(mb_strtolower($name), ['null', 'true', 'false'], true);
   }

    /**
     * @return Node\Expr\ConstFetch[]
     */
    public function getGlobalConstantList(): array
    {
        return $this->globalConstantList;
    }
}
