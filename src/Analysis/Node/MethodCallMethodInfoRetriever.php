<?php

namespace Serenata\Analysis\Node;

use UnexpectedValueException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Common\Position;

use PhpParser\Node;

use Serenata\Parsing\ToplevelTypeExtractorInterface;

use Serenata\Utility\TextDocumentItem;

/**
 * Fetches method information from a {@see Node\Expr\MethodCall}, {@see Node\Expr\StaticCall} or a {@see Node\Expr\New_}
 * node.
 */
final class MethodCallMethodInfoRetriever
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @var ToplevelTypeExtractorInterface
     */
    private $toplevelTypeExtractor;

    /**
     * @param NodeTypeDeducerInterface       $nodeTypeDeducer
     * @param ClasslikeInfoBuilderInterface  $classlikeInfoBuilder
     * @param ToplevelTypeExtractorInterface $toplevelTypeExtractor
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        ToplevelTypeExtractorInterface $toplevelTypeExtractor
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->toplevelTypeExtractor = $toplevelTypeExtractor;
    }

    /**
     * @param Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $node
     * @param TextDocumentItem                                         $textDocumentItem
     * @param Position                                                 $position
     *
     * @throws UnexpectedValueException when a dynamic method call is passed.
     * @throws UnexpectedValueException when the type the method is called on could not be determined.
     *
     * @return array[]
     */
    public function retrieve(Node\Expr $node, TextDocumentItem $textDocumentItem, Position $position): array
    {
        if ($node instanceof Node\Expr\New_) {
            if ($node->class instanceof Node\Expr) {
                // Can't currently deduce type of an expression such as "$this->{$foo}()";
                throw new UnexpectedValueException('Can\'t determine information of dynamic method call');
            } elseif ($node->class instanceof Node\Stmt\Class_) {
                throw new UnexpectedValueException('Can\'t determine information of anonymous class constructor call');
            }
        } elseif ($node->name instanceof Node\Expr) {
            // Can't currently deduce type of an expression such as "$this->{$foo}()";
            throw new UnexpectedValueException('Can\'t determine information of dynamic method call');
        }

        $objectNode = ($node instanceof Node\Expr\MethodCall) ? $node->var : $node->class;
        $methodName = ($node instanceof Node\Expr\New_) ? '__construct' : $node->name->name;

        $typeOfVar = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $objectNode,
            $textDocumentItem,
            $position
        ));

        $infoElements = [];

        foreach ($this->toplevelTypeExtractor->extract($typeOfVar) as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->build($type);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (!isset($info['methods'][$methodName])) {
                continue;
            }

            $infoElements[] = $info['methods'][$methodName];
        }

        return $infoElements;
    }
}
