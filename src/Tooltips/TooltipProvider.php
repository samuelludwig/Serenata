<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\Visiting\NodeFetchingVisitor;
use PhpIntegrator\Analysis\Visiting\NamespaceAttachingVisitor;
use PhpIntegrator\Analysis\Visiting\ResolvedNameAttachingVisitor;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Provides tooltips.
 */
class TooltipProvider
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var FuncCallNodeTooltipGenerator
     */
    protected $funcCallNodeTooltipGenerator;

    /**
     * @var ConstFetchNodeTooltipGenerator
     */
    protected $constFetchNodeTooltipGenerator;

    /**
     * @var ClassConstFetchNodeTooltipGenerator
     */
    protected $classConstFetchNodeTooltipGenerator;

    /**
     * @var NameNodeTooltipGenerator
     */
    protected $nameNodeTooltipGenerator;

    /**
     * @param Parser                              $parser
     * @param FuncCallNodeTooltipGenerator        $funcCallNodeTooltipGenerator
     * @param ConstFetchNodeTooltipGenerator      $constFetchNodeTooltipGenerator
     * @param ClassConstFetchNodeTooltipGenerator $classConstFetchNodeTooltipGenerator
     * @param NameNodeTooltipGenerator            $nameNodeTooltipGenerator
     */
    public function __construct(
        Parser $parser,
        FuncCallNodeTooltipGenerator $funcCallNodeTooltipGenerator,
        ConstFetchNodeTooltipGenerator $constFetchNodeTooltipGenerator,
        ClassConstFetchNodeTooltipGenerator $classConstFetchNodeTooltipGenerator,
        NameNodeTooltipGenerator $nameNodeTooltipGenerator
    ) {
        $this->parser = $parser;
        $this->funcCallNodeTooltipGenerator = $funcCallNodeTooltipGenerator;
        $this->constFetchNodeTooltipGenerator = $constFetchNodeTooltipGenerator;
        $this->classConstFetchNodeTooltipGenerator = $classConstFetchNodeTooltipGenerator;
        $this->nameNodeTooltipGenerator = $nameNodeTooltipGenerator;
    }

    /**
     * @param string $file
     * @param string $code
     * @param int    $position The position to analyze and show the tooltip for (byte offset).
     *
     * @return TooltipResult|null
     */
    public function get(string $file, string $code, int $position): ?TooltipResult
    {
        $nodes = [];

        try {
            $nodes = $this->getNodesFromCode($code);
            $node = $this->getNodeAt($nodes, $position);

            $contents = $this->getTooltipForNode($node, $file, $code);

            return new TooltipResult($contents);
        } catch (UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * @param array $nodes
     * @param int   $position
     *
     * @throws UnexpectedValueException
     *
     * @return Node
     */
    protected function getNodeAt(array $nodes, int $position): Node
    {
        $visitor = new NodeFetchingVisitor($position);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ResolvedNameAttachingVisitor());
        $traverser->addVisitor(new NamespaceAttachingVisitor());
        $traverser->addVisitor($visitor);

        $traverser->traverse($nodes);

        $node = $visitor->getNode();
        $nearestInterestingNode = $visitor->getNearestInterestingNode();

        if (!$node) {
            throw new UnexpectedValueException('No node found at location ' . $position);
        }

        if ($nearestInterestingNode instanceof Node\Expr\FuncCall ||
            $nearestInterestingNode instanceof Node\Expr\ConstFetch ||
            $nearestInterestingNode instanceof Node\Stmt\UseUse
        ) {
            return $nearestInterestingNode;
        }

        if ($nearestInterestingNode instanceof Node\Expr\StaticCall ||
            $nearestInterestingNode instanceof Node\Expr\StaticPropertyFetch ||
            $nearestInterestingNode instanceof Node\Expr\ClassConstFetch ||
            $nearestInterestingNode instanceof Node\Stmt\Class_
        ) {
            // We want different tooltips for the class name than for the actual member.
            return ($node instanceof Node\Name) ? $node : $nearestInterestingNode;
        }

        return $node;
    }

    /**
     * @param Node   $node
     * @param string $file
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForNode(Node $node, string $file, string $code): string
    {
        if ($node instanceof Node\Expr\FuncCall) {
            return $this->getTooltipForFuncCallNode($node);
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $this->getTooltipForConstFetchNode($node);
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            return $this->getTooltipForClassConstFetchNode($node, $file, $code);
        } elseif ($node instanceof Node\Stmt\UseUse) {
            return $this->getTooltipForUseUseNode($node, $file, $node->getAttribute('startLine'));
        } elseif ($node instanceof Node\Name) {
            return $this->getTooltipForNameNode($node, $file, $node->getAttribute('startLine'));
        }

        throw new UnexpectedValueException('Don\'t know how to handle node of type ' . get_class($node));
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForFuncCallNode(Node\Expr\FuncCall $node): string
    {
        if (!$node->name instanceof Node\Name) {
            throw new UnexpectedValueException('Determining tooltips for dynamic function calls is not supported');
        }

        return $this->funcCallNodeTooltipGenerator->generate($node);
    }

    /**
     * @param Node\Expr\ConstFetch $node
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForConstFetchNode(Node\Expr\ConstFetch $node): string
    {
        return $this->constFetchNodeTooltipGenerator->generate($node);
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param string                    $file
     * @param string                    $code
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForClassConstFetchNode(
        Node\Expr\ClassConstFetch $node,
        string $file,
        string $code
    ): string {
        return $this->classConstFetchNodeTooltipGenerator->generate($node, $file, $code);
    }

    /**
     * @param Node\Stmt\UseUse $node
     * @param string           $file
     * @param int              $line
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForUseUseNode(Node\Stmt\UseUse $node, string $file, int $line): string
    {
        // Use statements are always fully qualified, they aren't resolved.
        $nameNode = new Node\Name\FullyQualified($node->name->toString());

        return $this->nameNodeTooltipGenerator->generate($nameNode, $file, $line);
    }

    /**
     * @param Node\Name $node
     * @param string    $file
     * @param int       $line
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForNameNode(Node\Name $node, string $file, int $line): string
    {
        return $this->nameNodeTooltipGenerator->generate($node, $file, $line);
    }

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return Node[]
     */
    protected function getNodesFromCode(string $code): array
    {
        $nodes = $this->parser->parse($code, $this->getErrorHandler());

        if ($nodes === null) {
            throw new UnexpectedValueException('No nodes returned after parsing code');
        }

        return $nodes;
    }

    /**
     * @return ErrorHandler\Collecting
     */
    protected function getErrorHandler(): ErrorHandler\Collecting
    {
        return new ErrorHandler\Collecting();
    }
}
