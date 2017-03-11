<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\GlobalFunctionsProvider;

use PhpIntegrator\Analysis\Node\FunctionNameNodeFqsenDeterminer;

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
     * @var FunctionNameNodeFqsenDeterminer
     */
    protected $functionCallNodeFqsenDeterminer;

    /**
     * @var GlobalFunctionsProvider
     */
    protected $globalFunctionsProvider;

    /**
     * @param FunctionTooltipGenerator        $functionTooltipGenerator
     * @param FunctionNameNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     * @param GlobalFunctionsProvider         $globalFunctionsProvider
     */
    public function __construct(
        FunctionTooltipGenerator $functionTooltipGenerator,
        FunctionNameNodeFqsenDeterminer $functionCallNodeFqsenDeterminer,
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
     * @throws UnexpectedValueException when a dynamic function call is passed.
     *
     * @return string
     */
    public function generate(Node\Expr\FuncCall $node): string
    {
        if (!$node->name instanceof Node\Name) {
            throw new UnexpectedValueException('Fetching FQSEN of dynamic function calls is not supported');
        }

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine($node->name);

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
