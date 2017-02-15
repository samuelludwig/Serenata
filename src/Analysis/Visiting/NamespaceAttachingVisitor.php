<?php

namespace PhpIntegrator\Analysis\Visiting;

use PhpParser\Node;

/**
 * Visitor that attaches the active namespace to each node it traverses.
 */
class NamespaceAttachingVisitor extends AbstractNameResolvingVisitor
{
    /**
     * @var Node\Name
     */
    protected $lastNamespaceNode;

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        $node->setAttribute('namespace', $this->namespace);
    }
}
