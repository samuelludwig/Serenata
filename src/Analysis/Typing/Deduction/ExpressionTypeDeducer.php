<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\NameQualificationUtilities\PositionalNamespaceDeterminerInterface;

use Serenata\Parsing\LastExpressionParser;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Type deducer that returns the type of an expression at a specific offset in a file.
 */
final class ExpressionTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var LastExpressionParser
     */
    private $lastExpressionParser;

    /**
     * @var PositionalNamespaceDeterminerInterface
     */
    private $positionalNamespaceDeterminer;

    /**
     * @param NodeTypeDeducerInterface               $nodeTypeDeducer
     * @param LastExpressionParser                   $lastExpressionParser
     * @param PositionalNamespaceDeterminerInterface $positionalNamespaceDeterminer
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        LastExpressionParser $lastExpressionParser,
        PositionalNamespaceDeterminerInterface $positionalNamespaceDeterminer
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->lastExpressionParser = $lastExpressionParser;
        $this->positionalNamespaceDeterminer = $positionalNamespaceDeterminer;
    }

    /**
     * @param TextDocumentItem $textDocumentItem,
     * @param Position         $position
     * @param string|null      $expression
     * @param bool             $ignoreLastElement
     *
     * @return string[]
     */
    public function deduce(
        TextDocumentItem $textDocumentItem,
        Position $position,
        ?string $expression = null,
        bool $ignoreLastElement = false
    ): array {
        $node = $this->lastExpressionParser->getLastNodeAt(
            $expression !== null ? $expression : $textDocumentItem->getText(),
            $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE)
        );

        if ($node === null) {
            return [];
        } elseif ($node instanceof Node\Stmt\Expression) {
            $node = $node->expr;
        }

        if ($ignoreLastElement) {
            $node = $this->getNodeWithoutLastElement($node);
        }

        return $this->deduceTypesFromNode($node, $textDocumentItem, $position);
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    private function getNodeWithoutLastElement(Node $node): Node
    {
        if ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\PropertyFetch) {
            return $node->var;
        } elseif ($node instanceof Node\Expr\StaticCall ||
            $node instanceof Node\Expr\StaticPropertyFetch ||
            $node instanceof Node\Expr\ClassConstFetch
        ) {
            return $node->class;
        }

        return $node;
    }

    /**
     * @param Node             $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return string[]
     */
    private function deduceTypesFromNode(Node $node, TextDocumentItem $textDocumentItem, Position $position): array
    {
        // We're dealing with partial code, its context may be lost because of it being invalid, so we can't rely on
        // the namespace attaching visitor here.
        $this->attachRelevantNamespaceToNode($node, $textDocumentItem, $position);

        return $this->nodeTypeDeducer->deduce(new TypeDeductionContext($node, $textDocumentItem, $position));
    }

    /**
     * @param Node             $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return void
     */
    private function attachRelevantNamespaceToNode(
        Node $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): void {
        $namespace = null;
        $namespaceNode = null;

        $filePosition = new FilePosition($textDocumentItem->getUri(), $position);

        $namespace = $this->positionalNamespaceDeterminer->determine($filePosition);

        if ($namespace->getName() !== null) {
            $namespaceNode = new Node\Name\FullyQualified($namespace->getName());
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($namespaceNode) extends NodeVisitorAbstract {
            private $namespaceNode;

            public function __construct(?Node\Name $namespaceNode)
            {
                $this->namespaceNode = $namespaceNode;
            }

            public function enterNode(Node $node)
            {
                $node->setAttribute('namespace', $this->namespaceNode);
            }
        });

        $traverser->traverse([$node]);
    }
}
