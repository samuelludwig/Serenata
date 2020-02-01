<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Common\Position;

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
     * @param ConstantTooltipGenerator      $constantTooltipGenerator
     * @param NodeTypeDeducerInterface      $nodeTypeDeducer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     */
    public function __construct(
        ConstantTooltipGenerator $constantTooltipGenerator,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->constantTooltipGenerator = $constantTooltipGenerator;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws UnexpectedValueException when the constant name is not a string (i.e. an error node).
     * @throws UnexpectedValueException when the type of the class could not be determined.
     * @throws UnexpectedValueException when no tooltips could be determined.
     *
     * @return string
     */
    public function generate(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        if (!$node->name instanceof Node\Identifier) {
            throw new UnexpectedValueException("Can't deduce the type of a non-string node");
        }

        $classTypes = $this->getClassTypes($node, $textDocumentItem, $position);

        $tooltips = [];

        foreach ($classTypes as $classType) {
            $constantInfo = $this->fetchClassConstantInfo($classType, $node->name);

            if ($constantInfo === null) {
                continue;
            }

            $tooltips[] = $this->constantTooltipGenerator->generate($constantInfo);
        }

        if (count($tooltips) === 0) {
            throw new UnexpectedValueException('Could not determine any tooltips for the class constant');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        return $tooltips[0];
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws UnexpectedValueException
     *
     * @return array<string,mixed>
     */
    private function getClassTypes(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): array {
        $classTypes = [];

        try {
            $classTypes = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                $node->class,
                $textDocumentItem
            ));
        } catch (UnexpectedValueException $e) {
            throw new UnexpectedValueException('Could not deduce the type of class', 0, $e);
        }

        if (count($classTypes) === 0) {
            throw new UnexpectedValueException('No types returned for class');
        }

        return $classTypes;
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
        } catch (UnexpectedValueException $e) {
            return null;
        }

        if (!isset($classInfo['constants'][$name])) {
            return null;
        }

        return $classInfo['constants'][$name];
    }
}
