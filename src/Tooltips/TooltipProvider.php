<?php

namespace Serenata\Tooltips;

use LogicException;
use UnexpectedValueException;

use PhpParser\Node;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;

use Serenata\Common\Position;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

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
    * @param TextDocumentItem $textDocumentItem
    * @param Position         $position
     *
     * @return TooltipResult|null
     */
    public function get(TextDocumentItem $textDocumentItem, Position $position): ?TooltipResult
    {
        try {
            $node = $this->getNodeAt($textDocumentItem, $position);

            $contents = $this->getTooltipForNode($node, $textDocumentItem, $position);

            return new TooltipResult($contents);
        } catch (UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException
     *
     * @return Node
     */
    private function getNodeAt(TextDocumentItem $textDocumentItem, Position $position): Node
    {
        $result = $this->nodeAtOffsetLocator->locate($textDocumentItem, $position);

        $node = $result->getNode();
        $nearestInterestingNode = $result->getNearestInterestingNode();

        if (!$node) {
            throw new UnexpectedValueException(
                'No node found at location ' . $position->getLine() . ':' . $position->getCharacter()
            );
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
     * @param Node             $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForNode(Node $node, TextDocumentItem $textDocumentItem, Position $position): string
    {
        if ($node instanceof Node\Expr\FuncCall) {
            return $this->getTooltipForFuncCallNode($node, $textDocumentItem, $position);
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $this->getTooltipForConstFetchNode($node, $textDocumentItem, $position);
        } elseif ($node instanceof Node\Stmt\UseUse) {
            return $this->getTooltipForUseUseNode($node, $textDocumentItem, $position);
        } elseif ($node instanceof Node\Name) {
            return $this->getTooltipForNameNode($node, $textDocumentItem, $position);
        } elseif ($node instanceof Node\Identifier) {
            $parentNode = $node->getAttribute('parent', false);

            if ($parentNode === false) {
                throw new LogicException('No parent metadata attached to node');
            }

            if ($parentNode instanceof Node\Stmt\Function_) {
                return $this->getTooltipForFunctionNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Stmt\ClassMethod) {
                return $this->getTooltipForClassMethodNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\ClassConstFetch) {
                return $this->getTooltipForClassConstFetchNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\PropertyFetch) {
                return $this->getTooltipForPropertyFetchNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\StaticPropertyFetch) {
                return $this->getTooltipForStaticPropertyFetchNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\MethodCall) {
                return $this->getTooltipForMethodCallNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\StaticCall) {
                return $this->getTooltipForStaticMethodCallNode($parentNode, $textDocumentItem, $position);
            }
        }

        throw new UnexpectedValueException('Don\'t know how to handle node of type ' . get_class($node));
    }

    /**
     * @param Node\Expr\FuncCall $node
     * @param TextDocumentItem   $textDocumentItem
     * @param Position           $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForFuncCallNode(
        Node\Expr\FuncCall $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->funcCallNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\MethodCall $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForMethodCallNode(
        Node\Expr\MethodCall $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->methodCallNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\StaticCall $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForStaticMethodCallNode(
        Node\Expr\StaticCall $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->staticMethodCallNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\PropertyFetch $node
     * @param TextDocumentItem        $textDocumentItem
     * @param Position                $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForPropertyFetchNode(
        Node\Expr\PropertyFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->propertyFetchNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\StaticPropertyFetch $node
     * @param TextDocumentItem              $textDocumentItem
     * @param Position                      $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForStaticPropertyFetchNode(
        Node\Expr\StaticPropertyFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->staticPropertyFetchNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\ConstFetch $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForConstFetchNode(
        Node\Expr\ConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->constFetchNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForClassConstFetchNode(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->classConstFetchNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Stmt\UseUse $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForUseUseNode(
        Node\Stmt\UseUse $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        $parentNode = $node->getAttribute('parent', false);

        if ($parentNode === false) {
            throw new LogicException('Parent node data is required in metadata');
        }

        // Use statements are always fully qualified, they aren't resolved.
        $nameNode = new Node\Name\FullyQualified($node->name->toString());

        if ($parentNode instanceof Node\Stmt\GroupUse) {
            $nameNode = new Node\Name\FullyQualified(Node\Name::concat($parentNode->prefix, $nameNode));
        }

        return $this->nameNodeTooltipGenerator->generate($nameNode, $textDocumentItem, $position);
    }

    /**
     * @param Node\Stmt\Function_ $node
     * @param TextDocumentItem    $textDocumentItem
     * @param Position            $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForFunctionNode(
        Node\Stmt\Function_ $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->functionNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     * @param TextDocumentItem      $textDocumentItem
     * @param Position              $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForClassMethodNode(
        Node\Stmt\ClassMethod $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->classMethodNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Name        $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    private function getTooltipForNameNode(
        Node\Name $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        return $this->nameNodeTooltipGenerator->generate($node, $textDocumentItem, $position);
    }
}
