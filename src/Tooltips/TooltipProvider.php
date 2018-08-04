<?php

namespace Serenata\Tooltips;

use LogicException;
use UnexpectedValueException;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;

use Serenata\Indexing\Structures;

use PhpParser\Node;

/**
 * Provides tooltips.
 */
class TooltipProvider
{
    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var FuncCallNodeTooltipGenerator
     */
    private $funcCallNodeTooltipGenerator;

    /**
     * @var MethodCallNodeTooltipGenerator
     */
    private $methodCallNodeTooltipGenerator;

    /**
     * @var StaticMethodCallNodeTooltipGenerator
     */
    private $staticMethodCallNodeTooltipGenerator;

    /**
     * @var PropertyFetchNodeTooltipGenerator
     */
    private $propertyFetchNodeTooltipGenerator;

    /**
     * @var StaticPropertyFetchNodeTooltipGenerator
     */
    private $staticPropertyFetchNodeTooltipGenerator;

    /**
     * @var ConstFetchNodeTooltipGenerator
     */
    private $constFetchNodeTooltipGenerator;

    /**
     * @var ClassConstFetchNodeTooltipGenerator
     */
    private $classConstFetchNodeTooltipGenerator;

    /**
     * @var FunctionNodeTooltipGenerator
     */
    private $functionNodeTooltipGenerator;

    /**
     * @var ClassMethodNodeTooltipGenerator
     */
    private $classMethodNodeTooltipGenerator;

    /**
     * @var NameNodeTooltipGenerator
     */
    private $nameNodeTooltipGenerator;

    /**
     * @param NodeAtOffsetLocatorInterface            $nodeAtOffsetLocator
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
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
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
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
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
     * @param Structures\File $file
     * @param string          $code
     * @param int             $position The position to analyze and show the tooltip for (byte offset).
     *
     * @return TooltipResult|null
     */
    public function get(Structures\File $file, string $code, int $position): ?TooltipResult
    {
        try {
            $node = $this->getNodeAt($code, $position);

            $contents = $this->getTooltipForNode($node, $file, $code);

            return new TooltipResult($contents);
        } catch (UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * @param string $code
     * @param int    $position
     *
     * @throws UnexpectedValueException
     *
     * @return Node
     */
    private function getNodeAt(string $code, int $position): Node
    {
        $result = $this->nodeAtOffsetLocator->locate($code, $position);

        $node = $result->getNode();
        $nearestInterestingNode = $result->getNearestInterestingNode();

        if (!$node) {
            throw new UnexpectedValueException('No node found at location ' . $position);
        }

        if ($nearestInterestingNode instanceof Node\Expr\FuncCall ||
            $nearestInterestingNode instanceof Node\Expr\ConstFetch ||
            $nearestInterestingNode instanceof Node\Stmt\UseUse
        ) {
            return $nearestInterestingNode;
        }

        return ($node instanceof Node\Name || $node instanceof Node\Identifier) ? $node : $nearestInterestingNode;
    }

    /**
     * @param Node            $node
     * @param Structures\File $file
     * @param string          $code
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForNode(Node $node, Structures\File $file, string $code): string
    {
        if ($node instanceof Node\Expr\FuncCall) {
            return $this->getTooltipForFuncCallNode($node, $file, $code, $node->getAttribute('startFilePos'));
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $this->getTooltipForConstFetchNode($node, $file, $code, $node->getAttribute('startFilePos'));
        } elseif ($node instanceof Node\Stmt\UseUse) {
            return $this->getTooltipForUseUseNode($node, $file, $node->getAttribute('startLine'));
        } elseif ($node instanceof Node\Name) {
            return $this->getTooltipForNameNode($node, $file, $node->getAttribute('startLine'));
        } elseif ($node instanceof Node\Identifier) {
            $parentNode = $node->getAttribute('parent', false);

            if ($parentNode === false) {
                throw new LogicException('No parent metadata attached to node');
            }

            if ($parentNode instanceof Node\Stmt\Function_) {
                return $this->getTooltipForFunctionNode($parentNode, $file, $code);
            } elseif ($parentNode instanceof Node\Stmt\ClassMethod) {
                return $this->getTooltipForClassMethodNode($parentNode, $file);
            } elseif ($parentNode instanceof Node\Expr\ClassConstFetch) {
                return $this->getTooltipForClassConstFetchNode($parentNode, $file, $code);
            } elseif ($parentNode instanceof Node\Expr\PropertyFetch) {
                return $this->getTooltipForPropertyFetchNode(
                    $parentNode,
                    $file,
                    $code,
                    $parentNode->getAttribute('startFilePos')
                );
            } elseif ($parentNode instanceof Node\Expr\StaticPropertyFetch) {
                return $this->getTooltipForStaticPropertyFetchNode(
                    $parentNode,
                    $file,
                    $code,
                    $parentNode->getAttribute('startFilePos')
                );
            } elseif ($parentNode instanceof Node\Expr\MethodCall) {
                return $this->getTooltipForMethodCallNode(
                    $parentNode,
                    $file,
                    $code,
                    $parentNode->getAttribute('startFilePos')
                );
            } elseif ($parentNode instanceof Node\Expr\StaticCall) {
                return $this->getTooltipForStaticMethodCallNode(
                    $parentNode,
                    $file,
                    $code,
                    $parentNode->getAttribute('startFilePos')
                );
            }
        }

        throw new UnexpectedValueException('Don\'t know how to handle node of type ' . get_class($node));
    }

    /**
     * @param Node\Expr\FuncCall $node
     * @param Structures\File    $file
     * @param string             $code
     * @param int                $offset
     *
     * @throws unexpectedValueException
     *
     * @return string
     */
    private function getTooltipForFuncCallNode(
        Node\Expr\FuncCall $node,
        Structures\File $file,
        string $code,
        int $offset
    ): string {
        return $this->funcCallNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\MethodCall $node
     * @param Structures\File      $file
     * @param string               $code
     * @param int                  $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForMethodCallNode(
        Node\Expr\MethodCall $node,
        Structures\File $file,
        string $code,
        int $offset
    ): string {
        return $this->methodCallNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\StaticCall $node
     * @param Structures\File      $file
     * @param string               $code
     * @param int                  $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForStaticMethodCallNode(
        Node\Expr\StaticCall $node,
        Structures\File $file,
        string $code,
        int $offset
    ): string {
        return $this->staticMethodCallNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\PropertyFetch $node
     * @param Structures\File         $file
     * @param string                  $code
     * @param int                     $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForPropertyFetchNode(
        Node\Expr\PropertyFetch $node,
        Structures\File $file,
        string $code,
        int $offset
    ): string {
        return $this->propertyFetchNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\StaticPropertyFetch $node
     * @param Structures\File               $file
     * @param string                        $code
     * @param int                           $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForStaticPropertyFetchNode(
        Node\Expr\StaticPropertyFetch $node,
        Structures\File $file,
        string $code,
        int $offset
    ): string {
        return $this->staticPropertyFetchNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\ConstFetch $node
     * @param Structures\File      $file
     * @param string               $code
     * @param int                  $offset
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForConstFetchNode(
        Node\Expr\ConstFetch $node,
        Structures\File $file,
        string $code,
        int $offset
    ): string {
        return $this->constFetchNodeTooltipGenerator->generate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param Structures\File           $file
     * @param string                    $code
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForClassConstFetchNode(
        Node\Expr\ClassConstFetch $node,
        Structures\File $file,
        string $code
    ): string {
        return $this->classConstFetchNodeTooltipGenerator->generate($node, $file, $code);
    }

    /**
     * @param Node\Stmt\UseUse $node
     * @param Structures\File  $file
     * @param int              $line
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForUseUseNode(Node\Stmt\UseUse $node, Structures\File $file, int $line): string
    {
        $parentNode = $node->getAttribute('parent', false);

        if ($parentNode === false) {
            throw new LogicException('Parent node data is required in metadata');
        }

        // Use statements are always fully qualified, they aren't resolved.
        $nameNode = new Node\Name\FullyQualified($node->name->toString());

        if ($parentNode instanceof Node\Stmt\GroupUse) {
            $nameNode = new Node\Name\FullyQualified(Node\Name::concat($parentNode->prefix, $nameNode));
        }

        return $this->nameNodeTooltipGenerator->generate($nameNode, $file, $line);
    }

    /**
     * @param Node\Stmt\Function_ $node
     * @param Structures\File     $file
     * @param string              $source
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForFunctionNode(Node\Stmt\Function_ $node, Structures\File $file, string $source): string
    {
        return $this->functionNodeTooltipGenerator->generate($node, $file, $source, $node->getAttribute('startFilePos'));
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     * @param Structures\File       $file
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForClassMethodNode(Node\Stmt\ClassMethod $node, Structures\File $file): string
    {
        return $this->classMethodNodeTooltipGenerator->generate($node, $file);
    }

    /**
     * @param Node\Name       $node
     * @param Structures\File $file
     * @param int             $line
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForNameNode(Node\Name $node, Structures\File $file, int $line): string
    {
        return $this->nameNodeTooltipGenerator->generate($node, $file, $line);
    }
}
