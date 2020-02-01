<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Analysis\Node\FunctionCallNodeFqsenDeterminer;

use PhpParser\Node;

use Serenata\Common\Position;

use Serenata\Utility\TextDocumentItem;

/**
 * Provides tooltips for {@see Node\Expr\FuncCall} nodes.
 */
final class FuncCallNodeTooltipGenerator
{
    /**
     * @var FunctionTooltipGenerator
     */
    private $functionTooltipGenerator;

    /**
     * @var FunctionCallNodeFqsenDeterminer
     */
    private $functionCallNodeFqsenDeterminer;

    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @param FunctionTooltipGenerator        $functionTooltipGenerator
     * @param FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     * @param FunctionListProviderInterface   $functionListProvider
     */
    public function __construct(
        FunctionTooltipGenerator $functionTooltipGenerator,
        FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer,
        FunctionListProviderInterface $functionListProvider
    ) {
        $this->functionTooltipGenerator = $functionTooltipGenerator;
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
        $this->functionListProvider = $functionListProvider;
    }

     /**
      * @param Node\Expr\FuncCall $node
      * @param TextDocumentItem   $textDocumentItem
      * @param Position           $position
      *
      * @throws UnexpectedValueException when the function was not found.
      * @throws UnexpectedValueException when a dynamic function call is passed.
      *
      * @return string
      */
    public function generate(Node\Expr\FuncCall $node, TextDocumentItem $textDocumentItem, Position $position): string
    {
        if (!$node->name instanceof Node\Name) {
            throw new UnexpectedValueException('Fetching FQSEN of dynamic function calls is not supported');
        }

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine($node, $textDocumentItem->getUri(), $position);

        $info = $this->getFunctionInfo($fqsen);

        return $this->functionTooltipGenerator->generate($info);
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException
     *
     * @return array<string,mixed>
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
