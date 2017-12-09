<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use PhpIntegrator\Common\Position;
use PhpIntegrator\Common\FilePosition;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\NameQualificationUtilities\PositionalNamespaceDeterminerInterface;

use PhpIntegrator\Parsing\LastExpressionParser;

use PhpIntegrator\Utility\SourceCodeHelpers;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

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
     * @param Structures\File $file
     * @param string          $code
     * @param int             $offset
     * @param string|null     $expression
     * @param bool            $ignoreLastElement
     *
     * @return string[]
     */
    public function deduce(
        Structures\File $file,
        string $code,
        int $offset,
        ?string $expression = null,
        bool $ignoreLastElement = false
    ): array {
        $expression = $expression !== null ? $expression : $code;

        $node = $this->lastExpressionParser->getLastNodeAt($expression, $offset);

        if ($node === null) {
            return [];
        } elseif ($node instanceof Node\Stmt\Expression) {
            $node = $node->expr;
        }

        if ($ignoreLastElement) {
            $node = $this->getNodeWithoutLastElement($node);
        }

        return $this->deduceTypesFromNode($file, $code, $node, $offset);
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
     * @param Structures\File $file
     * @param string          $code
     * @param Node            $node
     * @param int             $offset
     *
     * @return string[]
     */
    private function deduceTypesFromNode(Structures\File $file, string $code, Node $node, int $offset): array
    {
        $line = SourceCodeHelpers::calculateLineByOffset($code, $offset);

        // We're dealing with partial code, its context may be lost because of it being invalid, so we can't rely on
        // the namespace attaching visitor here.
        $this->attachRelevantNamespaceToNode($node, $file, $line);

        return $this->nodeTypeDeducer->deduce($node, $file, $code, $offset);
    }

    /**
     * @param Node            $node
     * @param Structures\File $file
     * @param int             $line
     *
     * @return void
     */
    private function attachRelevantNamespaceToNode(Node $node, Structures\File $file, int $line): void
    {
        $namespace = null;
        $namespaceNode = null;

        $filePosition = new FilePosition($file->getPath(), new Position($line, 0));

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
