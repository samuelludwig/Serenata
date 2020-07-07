<?php

namespace Serenata\GotoDefinition;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\ClasslikeBuildingFailedException;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\TypeDeductionException;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Common\Position;

use Serenata\Parsing\ToplevelTypeExtractorInterface;

use Serenata\Utility\Location;
use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of the class constant called in {@see Node\Expr\ClassConstFetch} nodes.
 */
final class ClassConstFetchNodeDefinitionLocator
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
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws DefinitionLocationFailedException
     *
     * @return GotoDefinitionResponse
     */
    public function locate(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResponse {
        if (!$node->name instanceof Node\Identifier) {
            throw new DefinitionLocationFailedException("Can't deduce the type of a non-string node");
        }

        $classType = $this->getClassTypes($node, $textDocumentItem, $position);

        $definitions = [];

        foreach ($this->toplevelTypeExtractor->extract($classType) as $classType) {
            $constantInfo = $this->fetchClassConstantInfo($classType, $node->name);

            if ($constantInfo === null) {
                continue;
            }

            $definitions[] = new GotoDefinitionResponse(new Location($constantInfo['uri'], $constantInfo['range']));
        }

        if (count($definitions) === 0) {
            throw new DefinitionLocationFailedException('Could not determine any definition for the class constant');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        return $definitions[0];
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws DefinitionLocationFailedException
     *
     * @return TypeNode
     */
    private function getClassTypes(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): TypeNode {
        $classTypes = [];

        try {
            $classType = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                $node->class,
                $textDocumentItem,
                $position
            ));
        } catch (TypeDeductionException $e) {
            throw new DefinitionLocationFailedException('Could not deduce the type of class', 0, $e);
        }

        return $classType;
    }

    /**
     * @param string $classType
     * @param string $name
     *
     * @return array<string,mixed>|null
     */
    private function fetchClassConstantInfo(string $classType, string $name): ?array
    {
        $classInfo = null;

        try {
            $classInfo = $this->classlikeInfoBuilder->build($classType);
        } catch (ClasslikeBuildingFailedException $e) {
            return null;
        }

        if (!isset($classInfo['constants'][$name])) {
            return null;
        }

        return $classInfo['constants'][$name];
    }
}
