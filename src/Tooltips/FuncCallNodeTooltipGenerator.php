<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\GlobalFunctionsProvider;
use PhpIntegrator\Analysis\FunctionCallNodeFqsenDeterminer;

use PhpParser\Node;

/**
 * Provides tooltips for {@see Node\Expr\FuncCall} nodes.
 */
class FuncCallNodeTooltipGenerator
{
    /**
     * @var FunctionTooltipGenerator
     */
    protected $functionTooltipGenerator;

    /**
     * @var FunctionCallNodeFqsenDeterminer
     */
    protected $functionCallNodeFqsenDeterminer;

    /**
     * @var GlobalFunctionsProvider
     */
    protected $globalFunctionsProvider;

    /**
     * @param FunctionTooltipGenerator        $functionTooltipGenerator
     * @param FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     * @param GlobalFunctionsProvider         $globalFunctionsProvider
     */
    public function __construct(
        FunctionTooltipGenerator $functionTooltipGenerator,
        FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer,
        GlobalFunctionsProvider $globalFunctionsProvider
    ) {
        $this->functionTooltipGenerator = $functionTooltipGenerator;
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
        $this->globalFunctionsProvider = $globalFunctionsProvider;
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @throws UnexpectedValueException when the function was not found.
     *
     * @return string
     */
    public function generate(Node\Expr\FuncCall $node): string
    {
        $fqsen = $this->functionCallNodeFqsenDeterminer->determine($node);

        $info = $this->getFunctionInfo($fqsen);

        return $this->functionTooltipGenerator->generate($info);
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    protected function getFunctionInfo(string $fullyQualifiedName): array
    {
        $functions = $this->globalFunctionsProvider->getAll();

        if (!isset($functions[$fullyQualifiedName])) {
            throw new UnexpectedValueException('No data found for function with name ' . $fullyQualifiedName);
        }

        return $functions[$fullyQualifiedName];
    }
}
