<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Analysis\Node\FunctionNameNodeFqsenDeterminer;

use PhpParser\Node;

/**
 * Provides tooltips for {@see Node\Expr\FuncCall} nodes.
 */
class FuncCallNodeTooltipGenerator
{
    /**
     * @var FunctionTooltipGenerator
     */
    private $functionTooltipGenerator;

    /**
     * @var FunctionNameNodeFqsenDeterminer
     */
    private $functionCallNodeFqsenDeterminer;

    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @param FunctionTooltipGenerator        $functionTooltipGenerator
     * @param FunctionNameNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     * @param FunctionListProviderInterface   $functionListProvider
     */
    public function __construct(
        FunctionTooltipGenerator $functionTooltipGenerator,
        FunctionNameNodeFqsenDeterminer $functionCallNodeFqsenDeterminer,
        FunctionListProviderInterface $functionListProvider
    ) {
        $this->functionTooltipGenerator = $functionTooltipGenerator;
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
        $this->functionListProvider = $functionListProvider;
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
    private function getFunctionInfo(string $fullyQualifiedName): array
    {
        $functions = $this->functionListProvider->getAll();

        if (!isset($functions[$fullyQualifiedName])) {
            throw new UnexpectedValueException('No data found for function with name ' . $fullyQualifiedName);
        }

        return $functions[$fullyQualifiedName];
    }
}
