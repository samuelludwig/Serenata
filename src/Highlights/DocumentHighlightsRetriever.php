<?php

namespace Serenata\Highlights;

use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

use Serenata\Analysis\Node\NameNodeFqsenDeterminer;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;

use Serenata\Common\Position;

use Serenata\Utility\TextDocumentItem;

/**
 * Retrieves a list of highlights for a document.
 */
final class DocumentHighlightsRetriever
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var NameNodeFqsenDeterminer
     */
    private $nameNodeFqsenDeterminer;

    /**
     * @param Parser                       $parser
     * @param NodeAtOffsetLocatorInterface $nodeAtOffsetLocator
     * @param NameNodeFqsenDeterminer      $nameNodeFqsenDeterminer
     */
    public function __construct(
        Parser $parser,
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        NameNodeFqsenDeterminer $nameNodeFqsenDeterminer
    ) {
        $this->parser = $parser;
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->nameNodeFqsenDeterminer = $nameNodeFqsenDeterminer;
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return DocumentHighlight[]|null
     */
    public function retrieve(TextDocumentItem $textDocumentItem, Position $position): ?array
    {
        $result = $this->nodeAtOffsetLocator->locate($textDocumentItem, $position);

        $node = $result->getNode();

        if ($node === null) {
            return null;
        }

        $visitor = new DocumentHighlightsVisitor($this->nameNodeFqsenDeterminer, $textDocumentItem, $node);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($this->getNodes($textDocumentItem->getText()));

        return $visitor->getHighlights();
    }

    /**
     * @param string $code
     *
     * @throws Error
     *
     * @return array
     */
    private function getNodes(string $code): array
    {
        $handler = new ErrorHandler\Collecting();

        $nodes = $this->parser->parse($code, $handler);

        if ($nodes === null) {
            throw new Error('Unknown syntax error encountered');
        }

        return $nodes;
    }
}
