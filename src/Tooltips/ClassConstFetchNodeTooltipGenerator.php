<?php

namespace Serenata\Tooltips;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

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
 * Provides tooltips for {@see Node\Expr\ClassConstFetch} nodes.
 */
final class ClassConstFetchNodeTooltipGenerator
{
    /**
     * @var ConstantTooltipGenerator
     */
    private $constantTooltipGenerator;

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
     * @param ConstantTooltipGenerator       $constantTooltipGenerator
     * @param NodeTypeDeducerInterface       $nodeTypeDeducer
     * @param ClasslikeInfoBuilderInterface  $classlikeInfoBuilder
     * @param ToplevelTypeExtractorInterface $toplevelTypeExtractor
     */
    public function __construct(
        ConstantTooltipGenerator $constantTooltipGenerator,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        ToplevelTypeExtractorInterface $toplevelTypeExtractor
    ) {
        $this->constantTooltipGenerator = $constantTooltipGenerator;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->toplevelTypeExtractor = $toplevelTypeExtractor;
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws TooltipGenerationFailedException
     *
     * @return string
     */
    public function generate(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        if (!$node->name instanceof Node\Identifier) {
            throw new TooltipGenerationFailedException("Can't deduce the type of a non-string node");
        }

        $classType = $this->getClassTypes($node, $textDocumentItem, $position);

        $tooltips = [];

        foreach ($this->toplevelTypeExtractor->extract($classType) as $type) {
            $constantInfo = $this->fetchClassConstantInfo($type, $node->name);

            if ($constantInfo === null) {
                continue;
            }

            $tooltips[] = $this->constantTooltipGenerator->generate($constantInfo);
        }

        if (count($tooltips) === 0) {
            throw new TooltipGenerationFailedException('Could not determine any tooltips for the class constant');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        return $tooltips[0];
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws TooltipGenerationFailedException
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
                $textDocumentItem
            ));
        } catch (TypeDeductionException $e) {
            throw new TooltipGenerationFailedException('Could not deduce the type of class', 0, $e);
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
