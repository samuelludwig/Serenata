<?php

namespace Serenata\Parsing;

use PhpParser\Lexer;

use Serenata\Analysis\Visiting\ParentAttachingVisitor;
use Serenata\Analysis\Visiting\NamespaceAttachingVisitor;

use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

use PhpParser\NodeVisitor\NameResolver;

use Serenata\Analysis\Visiting\FunctionLikeBodyOffsetAttachingVisitor;

/**
 * Parser that delegates parsing to another parser and attaches metadata to the nodes.
 */
final class MetadataAttachingParser implements Parser
{
    /**
     * @var Parser
     */
    private $delegate;

    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @param Parser $delegate
     * @param Lexer  $lexer
     */
    public function __construct(Parser $delegate, Lexer $lexer)
    {
        $this->delegate = $delegate;
        $this->lexer = $lexer;
    }

    /**
     * @inheritDoc
     */
    public function parse(string $code, ?ErrorHandler $errorHandler = null)
    {
        $nodes = $this->delegate->parse($code, $errorHandler);

        if ($nodes === null) {
            return $nodes;
        }

        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver($errorHandler, [
            'replaceNodes' => false,
        ]));

        $traverser->addVisitor(new NamespaceAttachingVisitor($errorHandler));
        $traverser->addVisitor(new ParentAttachingVisitor());
        $traverser->addVisitor(new FunctionLikeBodyOffsetAttachingVisitor($this->lexer->getTokens()));

        $traverser->traverse($nodes);

        return $nodes;
    }
}
