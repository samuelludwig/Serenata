<?php

namespace Serenata\GotoDefinition;

use UnexpectedValueException;

use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Analysis\Node\FunctionNameNodeFqsenDeterminer;

use PhpParser\Node;

/**
 * Locates the definition of the function called in {@see Node\Expr\FuncCall} nodes.
 */
class FuncCallNodeDefinitionLocator
{
    /**
     * @var FunctionNameNodeFqsenDeterminer
     */
    private $functionCallNodeFqsenDeterminer;

    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @param FunctionNameNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     * @param FunctionListProviderInterface   $functionListProvider
     */
    public function __construct(
        FunctionNameNodeFqsenDeterminer $functionCallNodeFqsenDeterminer,
        FunctionListProviderInterface $functionListProvider
    ) {
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
        $this->functionListProvider = $functionListProvider;
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @throws UnexpectedValueException when the function was not found.
     * @throws UnexpectedValueException when a dynamic function call is passed.
     *
     * @return GotoDefinitionResult
     */
    public function locate(Node\Expr\FuncCall $node): GotoDefinitionResult
    {
        if (!$node->name instanceof Node\Name) {
            throw new UnexpectedValueException('Fetching FQSEN of dynamic function calls is not supported');
        }

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine($node->name);

        $info = $this->getFunctionInfo($fqsen);

        return new GotoDefinitionResult($info['filename'], $info['startLine']);
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
