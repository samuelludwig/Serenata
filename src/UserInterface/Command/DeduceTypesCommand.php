<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Common\Position;
use PhpIntegrator\Common\FilePosition;

use PhpIntegrator\NameQualificationUtilities\PositionalNamespaceDeterminerInterface;

use PhpIntegrator\Parsing\LastExpressionParser;

use PhpIntegrator\Utility\SourceCodeHelpers;
use PhpIntegrator\Utility\SourceCodeStreamReader;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Allows deducing the types of an expression (e.g. a call chain, a simple string, ...).
 */
class DeduceTypesCommand extends AbstractCommand
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
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var PositionalNamespaceDeterminerInterface
     */
    private $positionalNamespaceDeterminer;

    /**
     * @param NodeTypeDeducerInterface               $nodeTypeDeducer
     * @param LastExpressionParser                   $lastExpressionParser
     * @param SourceCodeStreamReader                 $sourceCodeStreamReader
     * @param PositionalNamespaceDeterminerInterface $positionalNamespaceDeterminer
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        LastExpressionParser $lastExpressionParser,
        SourceCodeStreamReader $sourceCodeStreamReader,
        PositionalNamespaceDeterminerInterface $positionalNamespaceDeterminer
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->lastExpressionParser = $lastExpressionParser;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->positionalNamespaceDeterminer = $positionalNamespaceDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        if (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A --file must be supplied!');
        } elseif (!isset($arguments['offset'])) {
            throw new InvalidArgumentsException('An --offset must be supplied into the source code!');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        }

        $offset = $arguments['offset'];

        if (isset($arguments['charoffset']) && $arguments['charoffset'] == true) {
            $offset = SourceCodeHelpers::getByteOffsetFromCharacterOffset($offset, $code);
        }

        $codeWithExpression = $code;

        if (isset($arguments['expression'])) {
            $codeWithExpression = $arguments['expression'];
        }

        $node = $this->lastExpressionParser->getLastNodeAt($codeWithExpression, $offset);

        if ($node === null) {
            return [];
        }

        if (isset($arguments['ignore-last-element']) && $arguments['ignore-last-element']) {
            $node = $this->getNodeWithoutLastElement($node);
        }

        $result = $this->deduceTypes(
           isset($arguments['file']) ? $arguments['file'] : null,
           $code,
           $node,
           $offset
        );

        return $result;
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    protected function getNodeWithoutLastElement(Node $node): Node
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
     * @param string $file
     * @param string $code
     * @param Node   $node
     * @param int    $offset
     *
     * @return string[]
     */
    protected function deduceTypes(string $file, string $code, Node $node, int $offset): array
    {
        $line = SourceCodeHelpers::calculateLineByOffset($code, $offset);

        // We're dealing with partial code, its context may be lost because of it being invalid, so we can't rely on
        // the namespace attaching visitor here.
        $this->attachRelevantNamespaceToNode($node, $file, $line);

        return $this->nodeTypeDeducer->deduce($node, $file, $code, $offset);
    }

    /**
     * @param string $file
     * @param string $code
     * @param string $expression
     * @param int    $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromExpression(string $file, string $code, string $expression, int $offset): array
    {
        $node = $this->lastExpressionParser->getLastNodeAt($expression, $offset);

        return $this->deduceTypes($file, $code, $node, $offset);
    }

    /**
     * @param Node   $node
     * @param string $filePath
     * @param int    $line
     *
     * @return void
     */
    protected function attachRelevantNamespaceToNode(Node $node, string $filePath, int $line): void
    {
        $namespace = null;
        $namespaceNode = null;

        $filePosition = new FilePosition($filePath, new Position($line, 0));

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
