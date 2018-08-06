<?php

namespace Serenata\GotoDefinition;

use LogicException;
use UnexpectedValueException;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use PhpParser\Node;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of structural elements.
 */
class DefinitionLocator
{
    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var FuncCallNodeDefinitionLocator
     */
    private $funcCallNodeDefinitionLocator;

    /**
     * @var MethodCallNodeDefinitionLocator
     */
    private $methodCallNodeDefinitionLocator;

    /**
     * @var ConstFetchNodeDefinitionLocator
     */
    private $constFetchNodeDefinitionLocator;

    /**
     * @var ClassConstFetchNodeDefinitionLocator
     */
    private $classConstFetchNodeDefinitionLocator;

    /**
     * @var NameNodeDefinitionLocator
     */
    private $nameNodeDefinitionLocator;

    /**
     * @var StaticMethodCallNodeDefinitionLocator
     */
    private $staticMethodCallNodeDefinitionLocator;

    /**
     * @var PropertyFetchDefinitionLocator
     */
    private $propertyFetchDefinitionLocator;

    /**
     * @var StaticPropertyFetchNodeDefinitionLocator
     */
    private $staticPropertyFetchNodeDefinitionLocator;

    /**
     * @param NodeAtOffsetLocatorInterface             $nodeAtOffsetLocator
     * @param FuncCallNodeDefinitionLocator            $funcCallNodeDefinitionLocator
     * @param MethodCallNodeDefinitionLocator          $methodCallNodeDefinitionLocator
     * @param ConstFetchNodeDefinitionLocator          $constFetchNodeDefinitionLocator
     * @param ClassConstFetchNodeDefinitionLocator     $classConstFetchNodeDefinitionLocator
     * @param NameNodeDefinitionLocator                $nameNodeDefinitionLocator
     * @param StaticMethodCallNodeDefinitionLocator    $staticMethodCallNodeDefinitionLocator
     * @param PropertyFetchDefinitionLocator           $propertyFetchDefinitionLocator
     * @param StaticPropertyFetchNodeDefinitionLocator $staticPropertyFetchNodeDefinitionLocator
     */
    public function __construct(
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        FuncCallNodeDefinitionLocator $funcCallNodeDefinitionLocator,
        MethodCallNodeDefinitionLocator $methodCallNodeDefinitionLocator,
        ConstFetchNodeDefinitionLocator $constFetchNodeDefinitionLocator,
        ClassConstFetchNodeDefinitionLocator $classConstFetchNodeDefinitionLocator,
        NameNodeDefinitionLocator $nameNodeDefinitionLocator,
        StaticMethodCallNodeDefinitionLocator $staticMethodCallNodeDefinitionLocator,
        PropertyFetchDefinitionLocator $propertyFetchDefinitionLocator,
        StaticPropertyFetchNodeDefinitionLocator $staticPropertyFetchNodeDefinitionLocator
    ) {
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->funcCallNodeDefinitionLocator = $funcCallNodeDefinitionLocator;
        $this->methodCallNodeDefinitionLocator = $methodCallNodeDefinitionLocator;
        $this->constFetchNodeDefinitionLocator = $constFetchNodeDefinitionLocator;
        $this->classConstFetchNodeDefinitionLocator = $classConstFetchNodeDefinitionLocator;
        $this->nameNodeDefinitionLocator = $nameNodeDefinitionLocator;
        $this->staticMethodCallNodeDefinitionLocator = $staticMethodCallNodeDefinitionLocator;
        $this->propertyFetchDefinitionLocator = $propertyFetchDefinitionLocator;
        $this->staticPropertyFetchNodeDefinitionLocator = $staticPropertyFetchNodeDefinitionLocator;
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return GotoDefinitionResult|null
     */
    public function locate(TextDocumentItem $textDocumentItem, Position $position): ?GotoDefinitionResult
    {
        try {
            $node = $this->getNodeAt(
                $textDocumentItem->getText(),
                $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE)
            );

            return $this->locateDefinitionOfStructuralElementRepresentedByNode($node, $textDocumentItem, $position);
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
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfStructuralElementRepresentedByNode(
        Node $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        if ($node instanceof Node\Expr\FuncCall) {
            return $this->locateDefinitionOfFuncCallNode($node, $textDocumentItem, $position);
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $this->locateDefinitionOfConstFetchNode($node, $textDocumentItem, $position);
        } elseif ($node instanceof Node\Stmt\UseUse) {
            return $this->locateDefinitionOfUseUseNode($node, $textDocumentItem, $position);
        } elseif ($node instanceof Node\Name) {
            return $this->locateDefinitionOfNameNode($node, $textDocumentItem, $position);
        } elseif ($node instanceof Node\Identifier) {
            $parentNode = $node->getAttribute('parent', false);

            if ($parentNode === false) {
                throw new LogicException('No parent metadata attached to node');
            }

            if ($parentNode instanceof Node\Expr\ClassConstFetch) {
                return $this->locateDefinitionOfClassConstFetchNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\PropertyFetch) {
                return $this->locateDefinitionOfPropertyFetchNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\StaticPropertyFetch) {
                return $this->locateDefinitionOfStaticPropertyFetchNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\MethodCall) {
                return $this->locateDefinitionOfMethodCallNode($parentNode, $textDocumentItem, $position);
            } elseif ($parentNode instanceof Node\Expr\StaticCall) {
                return $this->locateDefinitionOfStaticMethodCallNode($parentNode, $textDocumentItem, $position);
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
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfFuncCallNode(
        Node\Expr\FuncCall $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        return $this->funcCallNodeDefinitionLocator->locate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\MethodCall $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfMethodCallNode(
        Node\Expr\MethodCall $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        return $this->methodCallNodeDefinitionLocator->locate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\StaticCall $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfStaticMethodCallNode(
        Node\Expr\StaticCall $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        return $this->staticMethodCallNodeDefinitionLocator->locate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\PropertyFetch $node
     * @param TextDocumentItem        $textDocumentItem
     * @param Position                $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfPropertyFetchNode(
        Node\Expr\PropertyFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        return $this->propertyFetchDefinitionLocator->locate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\StaticPropertyFetch $node
     * @param TextDocumentItem              $textDocumentItem
     * @param Position                      $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfStaticPropertyFetchNode(
        Node\Expr\StaticPropertyFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        return $this->staticPropertyFetchNodeDefinitionLocator->locate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\ConstFetch $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfConstFetchNode(
        Node\Expr\ConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        return $this->constFetchNodeDefinitionLocator->generate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfClassConstFetchNode(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        return $this->classConstFetchNodeDefinitionLocator->locate($node, $textDocumentItem, $position);
    }

    /**
     * @param Node\Stmt\UseUse $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfUseUseNode(
        Node\Stmt\UseUse $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        $parentNode = $node->getAttribute('parent', false);

        if ($parentNode === false) {
            throw new LogicException('Parent node data is required in metadata');
        }

        // Use statements are always fully qualified, they aren't resolved.
        $nameNode = new Node\Name\FullyQualified($node->name->toString());

        if ($parentNode instanceof Node\Stmt\GroupUse) {
            $nameNode = new Node\Name\FullyQualified(Node\Name::concat($parentNode->prefix, $nameNode));
        }

        return $this->nameNodeDefinitionLocator->locate($nameNode, $textDocumentItem, $position);
    }

    /**
     * @param Node\Name        $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfNameNode(
        Node\Name $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        return $this->nameNodeDefinitionLocator->locate($node, $textDocumentItem, $position);
    }
}
