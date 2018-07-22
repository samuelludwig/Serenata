<?php

namespace Serenata\GotoDefinition;

use LogicException;
use UnexpectedValueException;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;

use Serenata\Indexing\Structures;

use PhpParser\Node;

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
     * @param Structures\File $file
     * @param string          $code
     * @param int             $position The position to analyze and show the tooltip for (byte offset).
     *
     * @return GotoDefinitionResult|null
     */
    public function locate(Structures\File $file, string $code, int $position): ?GotoDefinitionResult
    {
        try {
            $node = $this->getNodeAt($code, $position);

            return $this->locateDefinitionOfStructuralElementRepresentedByNode($node, $file, $code);
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
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfStructuralElementRepresentedByNode(
        Node $node,
        Structures\File $file,
        string $code
    ): GotoDefinitionResult {
        if ($node instanceof Node\Expr\FuncCall) {
            return $this->locateDefinitionOfFuncCallNode($node);
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $this->locateDefinitionOfConstFetchNode($node);
        } elseif ($node instanceof Node\Stmt\UseUse) {
            return $this->locateDefinitionOfUseUseNode($node, $file, $node->getAttribute('startLine'));
        } elseif ($node instanceof Node\Name) {
            return $this->locateDefinitionOfNameNode($node, $file, $node->getAttribute('startLine'));
        } elseif ($node instanceof Node\Identifier) {
            $parentNode = $node->getAttribute('parent', false);

            if ($parentNode === false) {
                throw new LogicException('No parent metadata attached to node');
            }

            if ($parentNode instanceof Node\Expr\ClassConstFetch) {
                return $this->locateDefinitionOfClassConstFetchNode($parentNode, $file, $code);
            } elseif ($parentNode instanceof Node\Expr\PropertyFetch) {
                return $this->locateDefinitionOfPropertyFetchNode(
                    $parentNode,
                    $file,
                    $code,
                    $parentNode->getAttribute('startFilePos')
                );
            } elseif ($parentNode instanceof Node\Expr\StaticPropertyFetch) {
                return $this->locateDefinitionOfStaticPropertyFetchNode(
                    $parentNode,
                    $file,
                    $code,
                    $parentNode->getAttribute('startFilePos')
                );
            } elseif ($parentNode instanceof Node\Expr\MethodCall) {
                return $this->locateDefinitionOfMethodCallNode(
                    $parentNode,
                    $file,
                    $code,
                    $parentNode->getAttribute('startFilePos')
                );
            } elseif ($parentNode instanceof Node\Expr\StaticCall) {
                return $this->locateDefinitionOfStaticMethodCallNode(
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
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfFuncCallNode(Node\Expr\FuncCall $node): GotoDefinitionResult
    {
        return $this->funcCallNodeDefinitionLocator->locate($node);
    }

    /**
     * @param Node\Expr\MethodCall $node
     * @param Structures\File      $file
     * @param string               $code
     * @param int                  $offset
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfMethodCallNode(
        Node\Expr\MethodCall $node,
        Structures\File $file,
        string $code,
        int $offset
    ): GotoDefinitionResult {
        return $this->methodCallNodeDefinitionLocator->locate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\StaticCall $node
     * @param Structures\File      $file
     * @param string               $code
     * @param int                  $offset
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfStaticMethodCallNode(
        Node\Expr\StaticCall $node,
        Structures\File $file,
        string $code,
        int $offset
    ): GotoDefinitionResult {
        return $this->staticMethodCallNodeDefinitionLocator->locate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\PropertyFetch $node
     * @param Structures\File         $file
     * @param string                  $code
     * @param int                     $offset
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfPropertyFetchNode(
        Node\Expr\PropertyFetch $node,
        Structures\File $file,
        string $code,
        int $offset
    ): GotoDefinitionResult {
        return $this->propertyFetchDefinitionLocator->locate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\StaticPropertyFetch $node
     * @param Structures\File               $file
     * @param string                        $code
     * @param int                           $offset
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfStaticPropertyFetchNode(
        Node\Expr\StaticPropertyFetch $node,
        Structures\File $file,
        string $code,
        int $offset
    ): GotoDefinitionResult {
        return $this->staticPropertyFetchNodeDefinitionLocator->locate($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\ConstFetch $node
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfConstFetchNode(Node\Expr\ConstFetch $node): GotoDefinitionResult
    {
        return $this->constFetchNodeDefinitionLocator->generate($node);
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param Structures\File           $file
     * @param string                    $code
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfClassConstFetchNode(
        Node\Expr\ClassConstFetch $node,
        Structures\File $file,
        string $code
    ): GotoDefinitionResult {
        return $this->classConstFetchNodeDefinitionLocator->locate($node, $file, $code);
    }

    /**
     * @param Node\Stmt\UseUse $node
     * @param Structures\File  $file
     * @param int              $line
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfUseUseNode(
        Node\Stmt\UseUse $node,
        Structures\File $file,
        int $line
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

        return $this->nameNodeDefinitionLocator->locate($nameNode, $file, $line);
    }

    /**
     * @param Node\Name       $node
     * @param Structures\File $file
     * @param int             $line
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    private function locateDefinitionOfNameNode(
        Node\Name $node,
        Structures\File $file,
        int $line
    ): GotoDefinitionResult {
        return $this->nameNodeDefinitionLocator->locate($node, $file, $line);
    }
}
