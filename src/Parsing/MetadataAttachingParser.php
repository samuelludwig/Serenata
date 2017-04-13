<?php

namespace PhpIntegrator\Parsing;

use PhpIntegrator\Analysis\Visiting\ParentAttachingVisitor;
use PhpIntegrator\Analysis\Visiting\NamespaceAttachingVisitor;
use PhpIntegrator\Analysis\Visiting\ResolvedNameAttachingVisitor;

use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Parser that delegates parsing to another parser and attaches metadata to the nodes.
 */
class MetadataAttachingParser implements Parser
{
    /**
     * @var Parser
     */
    private $delegate;

    /**
     * @param Parser $delegate
     */
    public function __construct(Parser $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function parse($code, ErrorHandler $errorHandler = null)
    {
        $nodes = $this->delegate->parse($code, $errorHandler);

        if ($nodes === null) {
            return $nodes;
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ResolvedNameAttachingVisitor());
        $traverser->addVisitor(new NamespaceAttachingVisitor());
        $traverser->addVisitor(new ParentAttachingVisitor());

        $traverser->traverse($nodes);

        return $nodes;
    }
}