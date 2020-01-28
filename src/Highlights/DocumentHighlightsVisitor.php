<?php

namespace Serenata\Highlights;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use Serenata\Analysis\Node\NameNodeFqsenDeterminer;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\NodeHelpers;
use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Visitor that generates a list of highlights for a tree.
 */
final class DocumentHighlightsVisitor extends NodeVisitorAbstract
{
    /**
     * @var NameNodeFqsenDeterminer
     */
    private $nameNodeFqsenDeterminer;

    /**
     * @var TextDocumentItem
     */
    private $textDocumentItem;

    /**
     * @var Node
     */
    private $referenceNode;

    /**
     * @var DocumentHighlight[]
     */
    private $highlights = [];

    /**
     * @param NameNodeFqsenDeterminer $nameNodeFqsenDeterminer
     * @param Node                    $referenceNode
     */
    public function __construct(
        NameNodeFqsenDeterminer $nameNodeFqsenDeterminer,
        TextDocumentItem $textDocumentItem,
        Node $referenceNode
    ) {
        $this->nameNodeFqsenDeterminer = $nameNodeFqsenDeterminer;
        $this->textDocumentItem = $textDocumentItem;
        $this->referenceNode = $referenceNode;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        $nodeFqcn = $this->findFqcnForNode($node);

        if ($nodeFqcn === null) {
            return null;
        }

        $referenceFqcn = $this->findFqcnForNode($this->referenceNode);

        if ($referenceFqcn === null) {
            return null;
        }

        if ($nodeFqcn === $referenceFqcn) {
            $this->pushHighlight($node);
        }

        return null;
    }

    /**
     * @param Node $node
     *
     * @return string|null
     */
    private function findFqcnForNode(Node $node): ?string
    {
        $nodeToResolve = null;

        if ($node instanceof Node\Name) {
            $nodeToResolve = $node;
        } elseif ($node instanceof Node\Identifier) {
            $nodeToResolve = new Node\Name($node->name);
        } else {
            return null;
        }

        if (NodeHelpers::findAncestorOfAnyType($node, Node\Stmt\Use_::class) !== null) {
            $name = $node->toString();

            if ($name[0] !== '\\') {
                return '\\' . $name;
            }

            return $name;
        }

        return $this->nameNodeFqsenDeterminer->determine(
            $nodeToResolve,
            $this->textDocumentItem,
            Position::createFromByteOffset(
                $node->getAttribute('startFilePos') + 1,
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            )
        );
    }

    /**
     * @param Node $node
     */
    private function pushHighlight(Node $node): void
    {
        $this->highlights[] = new DocumentHighlight(
            new Range(
                Position::createFromByteOffset(
                    $node->getAttribute('startFilePos'),
                    $this->textDocumentItem->getText(),
                    PositionEncoding::VALUE
                ),
                Position::createFromByteOffset(
                    $node->getAttribute('endFilePos') + 1,
                    $this->textDocumentItem->getText(),
                    PositionEncoding::VALUE
                )
            ),
            null
        );
    }

    /**
     * @return DocumentHighlight[]
     */
    public function getHighlights(): array
    {
        return $this->highlights;
    }
}
