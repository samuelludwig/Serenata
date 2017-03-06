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
     * @var MethodCallNodeTooltipGenerator
     */
    protected $methodCallNodeTooltipGenerator;

    /**
     * @var StaticMethodCallNodeTooltipGenerator
     */
    protected $staticMethodCallNodeTooltipGenerator;

    /**
     * @var PropertyFetchNodeTooltipGenerator
     */
    protected $propertyFetchNodeTooltipGenerator;

    /**
     * @var StaticPropertyFetchNodeTooltipGenerator
     */
    protected $staticPropertyFetchNodeTooltipGenerator;

    /**
     * @var ConstFetchNodeTooltipGenerator
     */
    protected $constFetchNodeTooltipGenerator;

    /**
     * @var ClassConstFetchNodeTooltipGenerator
     */
    protected $classConstFetchNodeTooltipGenerator;

    /**
     * @var FunctionNodeTooltipGenerator
     */
    protected $functionNodeTooltipGenerator;

    /**
     * @var ClassMethodNodeTooltipGenerator
     */
    protected $classMethodNodeTooltipGenerator;

    /**
     * @var NameNodeTooltipGenerator
     */
    protected $nameNodeTooltipGenerator;

    /**
     * @param Parser                                  $parser
     * @param FuncCallNodeTooltipGenerator            $funcCallNodeTooltipGenerator
     * @param MethodCallNodeTooltipGenerator          $methodCallNodeTooltipGenerator
     * @param StaticMethodCallNodeTooltipGenerator    $staticMethodCallNodeTooltipGenerator
     * @param PropertyFetchNodeTooltipGenerator       $propertyFetchNodeTooltipGenerator
     * @param StaticPropertyFetchNodeTooltipGenerator $staticPropertyFetchNodeTooltipGenerator
     * @param ConstFetchNodeTooltipGenerator          $constFetchNodeTooltipGenerator
     * @param ClassConstFetchNodeTooltipGenerator     $classConstFetchNodeTooltipGenerator
     * @param FunctionNodeTooltipGenerator            $functionNodeTooltipGenerator
     * @param ClassMethodNodeTooltipGenerator         $classMethodNodeTooltipGenerator
     * @param NameNodeTooltipGenerator                $nameNodeTooltipGenerator
     */
    public function __construct(
        Parser $parser,
        FuncCallNodeTooltipGenerator $funcCallNodeTooltipGenerator,
        MethodCallNodeTooltipGenerator $methodCallNodeTooltipGenerator,
        StaticMethodCallNodeTooltipGenerator $staticMethodCallNodeTooltipGenerator,
        PropertyFetchNodeTooltipGenerator $propertyFetchNodeTooltipGenerator,
        StaticPropertyFetchNodeTooltipGenerator $staticPropertyFetchNodeTooltipGenerator,
        ConstFetchNodeTooltipGenerator $constFetchNodeTooltipGenerator,
        ClassConstFetchNodeTooltipGenerator $classConstFetchNodeTooltipGenerator,
        FunctionNodeTooltipGenerator $functionNodeTooltipGenerator,
        ClassMethodNodeTooltipGenerator $classMethodNodeTooltipGenerator,
        NameNodeTooltipGenerator $nameNodeTooltipGenerator
    ) {
        $this->parser = $parser;
        $this->funcCallNodeTooltipGenerator = $funcCallNodeTooltipGenerator;
        $this->methodCallNodeTooltipGenerator = $methodCallNodeTooltipGenerator;
        $this->staticMethodCallNodeTooltipGenerator = $staticMethodCallNodeTooltipGenerator;
        $this->propertyFetchNodeTooltipGenerator = $propertyFetchNodeTooltipGenerator;
        $this->staticPropertyFetchNodeTooltipGenerator = $staticPropertyFetchNodeTooltipGenerator;
        $this->constFetchNodeTooltipGenerator = $constFetchNodeTooltipGenerator;
        $this->classConstFetchNodeTooltipGenerator = $classConstFetchNodeTooltipGenerator;
        $this->functionNodeTooltipGenerator = $functionNodeTooltipGenerator;
        $this->classMethodNodeTooltipGenerator = $classMethodNodeTooltipGenerator;
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

        return ($node instanceof Node\Name) ? $node : $nearestInterestingNode;
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
        } elseif ($node instanceof Node\Expr\MethodCall) {
            return $this->getTooltipForMethodCallNode($node, $file, $code, $node->getAttribute('startFilePos'));
        } elseif ($node instanceof Node\Expr\StaticCall) {
            return $this->getTooltipForStaticMethodCallNode($node, $file, $code, $node->getAttribute('startFilePos'));
        } elseif ($node instanceof Node\Expr\PropertyFetch) {
            return $this->getTooltipForPropertyFetchNode($node, $file, $code, $node->getAttribute('startFilePos'));
        } elseif ($node instanceof Node\Expr\StaticPropertyFetch) {
            return $this->getTooltipForStaticPropertyFetchNode($node, $file, $code, $node->getAttribute('startFilePos'));
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $this->getTooltipForConstFetchNode($node);
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            return $this->getTooltipForClassConstFetchNode($node, $file, $code);
        } elseif ($node instanceof Node\Stmt\UseUse) {
            return $this->getTooltipForUseUseNode($node, $file, $node->getAttribute('startLine'));
        } elseif ($node instanceof Node\Stmt\Function_) {
            return $this->getTooltipForFunctionNode($node);
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            return $this->getTooltipForClassMethodNode($node, $file);
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
        return $this->funcCallNodeTooltipGenerator->generate($node);
    }

    /**
     * @param Node\Expr\MethodCall $node
     * @param string               $file
     * @param string               $code
     * @param int                  $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForMethodCallNode(
        Node\Expr\MethodCall $node,
        string $file,
        string $code,
        int $offset
    ): string {
        return $this->methodCallNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\StaticCall $node
     * @param string               $file
     * @param string               $code
     * @param int                  $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForStaticMethodCallNode(
        Node\Expr\StaticCall $node,
        string $file,
        string $code,
        int $offset
    ): string {
        return $this->staticMethodCallNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\PropertyFetch $node
     * @param string                  $file
     * @param string                  $code
     * @param int                     $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForPropertyFetchNode(
        Node\Expr\PropertyFetch $node,
        string $file,
        string $code,
        int $offset
    ): string {
        return $this->propertyFetchNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\StaticPropertyFetch $node
     * @param string                        $file
     * @param string                        $code
     * @param int                           $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForStaticPropertyFetchNode(
        Node\Expr\StaticPropertyFetch $node,
        string $file,
        string $code,
        int $offset
    ): string {
        return $this->staticPropertyFetchNodeTooltipGenerator->generate($node, $file, $code, $offset);
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
     * @param Node\Stmt\Function_ $node
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForFunctionNode(Node\Stmt\Function_ $node): string
    {
        return $this->functionNodeTooltipGenerator->generate($node);
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     * @param string                $filePath
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    protected function getTooltipForClassMethodNode(Node\Stmt\ClassMethod $node, string $filePath): string
    {
        return $this->classMethodNodeTooltipGenerator->generate($node, $filePath);
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