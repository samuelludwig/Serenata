<?php

namespace Serenata\Analysis\Node;

use UnexpectedValueException;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Common\Position;

use Serenata\Utility\TextDocumentItem;

/**
 * Fetches method information from a {@see Node\Expr\PropertyFetch} or a {@see Node\Expr\StaticPropertyFetch} node.
 */
final class PropertyFetchPropertyInfoRetriever
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
     * @param NodeTypeDeducerInterface      $nodeTypeDeducer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $node
     * @param TextDocumentItem                                      $textDocumentItem
     * @param Position                                              $position
     *
     * @throws UnexpectedValueException when a dynamic property fetch is passed.
     * @throws UnexpectedValueException when the type the property is fetched from could not be determined.
     *
     * @return array<array<string,mixed>>
     */
    public function retrieve(Node\Expr $node, TextDocumentItem $textDocumentItem, Position $position): array
    {
        if ($node->name instanceof Node\Expr) {
            // Can't currently deduce type of an expression such as "$this->{$foo}";
            throw new UnexpectedValueException('Can\'t determine information of dynamic property fetch');
        }

        $objectNode = ($node instanceof Node\Expr\PropertyFetch) ? $node->var : $node->class;

        $typesOfVar = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $objectNode,
            $textDocumentItem,
            $position
        ));

        $infoElements = [];

        foreach ($typesOfVar as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->build($type);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (!isset($info['properties'][$node->name->name])) {
                continue;
            }

            $infoElements[] = $info['properties'][$node->name->name];
        }

        return $infoElements;
    }
}
