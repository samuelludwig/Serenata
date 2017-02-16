<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

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
     * @param FunctionTooltipGenerator        $functionTooltipGenerator
     * @param FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     */
    public function __construct(
        FunctionTooltipGenerator $functionTooltipGenerator,
        FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
    ) {
        $this->functionTooltipGenerator = $functionTooltipGenerator;
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
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

        return $this->functionTooltipGenerator->generate($fqsen);
    }
}
