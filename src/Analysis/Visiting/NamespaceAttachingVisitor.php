<?php

namespace Serenata\Analysis\Visiting;

use PhpParser\Node;
use PhpParser\ErrorHandler;

use PhpParser\NodeVisitor\NameResolver;

/**
 * Visitor that attaches the active namespace to each node it traverses.
 */
final class NamespaceAttachingVisitor extends NameResolver
{
    /**
     * @param ErrorHandler|null $errorHandler
     */
    public function __construct(?ErrorHandler $errorHandler = null)
    {
        parent::__construct($errorHandler, [
            'replaceNodes' => false,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        $node->setAttribute('namespace', $this->nameContext->getNamespace());

        return null;
    }
}
