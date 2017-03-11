<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\GlobalFunctionsProvider;

use PhpIntegrator\Analysis\Node\FunctionNameNodeFqsenDeterminer;

use PhpParser\Node;

/**
 * Provides tooltips for {@see Node\Stmt\Function_} nodes.
 */
class FunctionNodeTooltipGenerator
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
     * @param Node\Stmt\Function_ $node
     *
     * @throws UnexpectedValueException when the function was not found.
     *
     * @return string
     */
    public function generate(Node\Stmt\Function_ $node): string
    {
        $nameNode = new Node\Name\Relative($node->name);
        $nameNode->setAttribute('namespace', $node->getAttribute('namespace'));

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine($nameNode);

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
