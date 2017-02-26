<?php

namespace PhpIntegrator\Tooltips;

use LogicException;
use UnexpectedValueException;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;
use PhpIntegrator\Analysis\GlobalFunctionsProvider;
use PhpIntegrator\Analysis\FilePositionClasslikeDeterminer;
use PhpIntegrator\Analysis\FunctionNameNodeFqsenDeterminer;

use PhpIntegrator\Utility\Position;

use PhpParser\Node;

/**
 * Provides tooltips for {@see Node\Stmt\ClassMethod} nodes.
 */
class ClassMethodNodeTooltipGenerator
{
    /**
     * @var FunctionTooltipGenerator
     */
    protected $functionTooltipGenerator;

    /**
     * @var FilePositionClasslikeDeterminer
     */
    protected $filePositionClasslikeDeterminer;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @param FunctionTooltipGenerator        $functionTooltipGenerator
     * @param FilePositionClasslikeDeterminer $filePositionClasslikeDeterminer
     * @param ClasslikeInfoBuilder            $classlikeInfoBuilder
     */
    public function __construct(
        FunctionTooltipGenerator $functionTooltipGenerator,
        FilePositionClasslikeDeterminer $filePositionClasslikeDeterminer,
        ClasslikeInfoBuilder $classlikeInfoBuilder
    ) {
        $this->functionTooltipGenerator = $functionTooltipGenerator;
        $this->filePositionClasslikeDeterminer = $filePositionClasslikeDeterminer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     * @param string                $filePath
     *
     * @throws UnexpectedValueException when the method was not found.
     * @throws UnexpectedValueException when no class was found at the location of the node.
     *
     * @return string
     */
    public function generate(Node\Stmt\ClassMethod $node, string $filePath): string
    {
        $startLine = $node->getAttribute('startLine');

        if ($startLine === null) {
            throw new LogicException('Nodes must have startLine metadata attached');
        }

        $position = new Position($startLine, 0);

        $fqcn = $this->filePositionClasslikeDeterminer->determine($position, $filePath);

        if ($fqcn === null) {
            throw new UnexpectedValueException('No class found at location of method call node');
        }

        $methodInfo = $this->getMethodInfo($fqcn, $node->name);

        return $this->functionTooltipGenerator->generate($methodInfo);
    }

    /**
     * @param string $fqcn
     * @param string $method
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    protected function getMethodInfo(string $fqcn, string $method): array
    {
        $classlikeInfo = null;

        try {
            $classlikeInfo = $this->classlikeInfoBuilder->getClasslikeInfo($fqcn);
        } catch (UnexpectedValueException $e) {
            throw new UnexpectedValueException(
                'Could not find class with name ' . $fqcn . ' for method call node',
                0,
                $e
            );
        }

        if (!isset($classlikeInfo['methods'][$method])) {
            throw new UnexpectedValueException('No method ' . $method . ' exists for class ' . $fqcn);
        }

        return $classlikeInfo['methods'][$method];
    }
}
