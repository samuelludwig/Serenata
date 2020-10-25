<?php

namespace Serenata\Analysis\Node;

use UnexpectedValueException;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\ClasslikeBuildingFailedException;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\TypeDeductionException;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Common\Position;

use Serenata\Parsing\ToplevelTypeExtractorInterface;

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
     * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $node
     * @param TextDocumentItem                                      $textDocumentItem
     * @param Position                                              $position
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

        try {
            $typeOfVar = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                $objectNode,
                $textDocumentItem,
                $position
            ));
        } catch (TypeDeductionException $e) {
            throw new UnexpectedValueException('Could not fetch property fetch property info', 0, $e);
        }

        $infoElements = [];

        foreach ($this->toplevelTypeExtractor->extract($typeOfVar) as $type) {
            $info = null;

            if ($type instanceof GenericTypeNode) {
                // Not entirely correct, but we can't resolve templates yet, so ignore them for now so we can keep
                // resolving without breaking on generic syntax.
                $type = $type->type;
            }

            try {
                $info = $this->classlikeInfoBuilder->build((string) $type);
            } catch (ClasslikeBuildingFailedException $e) {
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
