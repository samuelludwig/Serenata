<?php

namespace Serenata\Tooltips;

use LogicException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\FilePositionClasslikeDeterminer;
use Serenata\Analysis\ClasslikeBuildingFailedException;

use Serenata\Common\Position;

use PhpParser\Node;

use Serenata\Utility\TextDocumentItem;

/**
 * Provides tooltips for {@see Node\Stmt\ClassMethod} nodes.
 */
final class ClassMethodNodeTooltipGenerator
{
    /**
     * @var FunctionTooltipGenerator
     */
    private $functionTooltipGenerator;

    /**
     * @var FilePositionClasslikeDeterminer
     */
    private $filePositionClasslikeDeterminer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param FunctionTooltipGenerator        $functionTooltipGenerator
     * @param FilePositionClasslikeDeterminer $filePositionClasslikeDeterminer
     * @param ClasslikeInfoBuilderInterface   $classlikeInfoBuilder
     */
    public function __construct(
        FunctionTooltipGenerator $functionTooltipGenerator,
        FilePositionClasslikeDeterminer $filePositionClasslikeDeterminer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->functionTooltipGenerator = $functionTooltipGenerator;
        $this->filePositionClasslikeDeterminer = $filePositionClasslikeDeterminer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     * @param TextDocumentItem      $textDocumentItem
     *
     * @throws TooltipGenerationFailedException when no method or class was found at the location of the node.
     *
     * @return string
     */
    public function generate(
        Node\Stmt\ClassMethod $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        $startLine = $node->getAttribute('startLine');

        if ($startLine === null) {
            throw new LogicException('Nodes must have startLine metadata attached');
        }

        $position = new Position($startLine, 0);

        $fqcn = $this->filePositionClasslikeDeterminer->determine($textDocumentItem, $position);

        if ($fqcn === null) {
            throw new TooltipGenerationFailedException('No class found at location of method call node');
        }

        $methodInfo = $this->getMethodInfo($fqcn, $node->name);

        return $this->functionTooltipGenerator->generate($methodInfo);
    }

    /**
     * @param string $fqcn
     * @param string $method
     *
     * @throws TooltipGenerationFailedException
     *
     * @return array<string,mixed>
     */
    private function getMethodInfo(string $fqcn, string $method): array
    {
        $classlikeInfo = null;

        try {
            $classlikeInfo = $this->classlikeInfoBuilder->build($fqcn);
        } catch (ClasslikeBuildingFailedException $e) {
            throw new TooltipGenerationFailedException(
                'Could not find class with name ' . $fqcn . ' for method call node',
                0,
                $e
            );
        }

        if (!isset($classlikeInfo['methods'][$method])) {
            throw new TooltipGenerationFailedException('No method ' . $method . ' exists for class ' . $fqcn);
        }

        return $classlikeInfo['methods'][$method];
    }
}
