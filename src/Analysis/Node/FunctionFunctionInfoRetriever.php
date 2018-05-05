<?php

namespace Serenata\Analysis\Node;

use AssertionError;
use UnexpectedValueException;

use Serenata\Analysis\FunctionListProviderInterface;

use PhpParser\Node;

/**
 * Fetches method information from a {@see Node\Expr\FuncCall} or a {@see Node\Stmt\Function_} node.
 */
class FunctionFunctionInfoRetriever
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
     * @param Node\Expr\FuncCall|Node\Stmt\Function_ $node
     *
     * @throws UnexpectedValueException when the function wasn't found.
     *
     * @return array
     */
    public function retrieve(Node $node): array
    {
        if (!$node instanceof Node\Expr\FuncCall && !$node instanceof Node\Stmt\Function_) {
            throw new AssertionError('Expected function node, got ' . get_class($node) . ' instead');
        } elseif ($node->name instanceof Node\Expr) {
            throw new UnexpectedValueException(
                'Determining the info for dynamic function calls is currently not supported'
            );
        }

        $nameNode = new Node\Name\Relative((string) $node->name);
        $nameNode->setAttribute('namespace', $node->getAttribute('namespace'));

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine($nameNode);

        return $this->getFunctionInfo($fqsen);
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
