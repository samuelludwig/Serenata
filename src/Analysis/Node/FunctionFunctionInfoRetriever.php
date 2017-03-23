<?php

namespace PhpIntegrator\Analysis\Node;

use LogicException;
use UnexpectedValueException;

use PhpIntegrator\Analysis\GlobalFunctionsProvider;

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
     * @var GlobalFunctionsProvider
     */
    private $globalFunctionsProvider;

    /**
     * @param FunctionNameNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     * @param GlobalFunctionsProvider         $globalFunctionsProvider
     */
    public function __construct(
        FunctionNameNodeFqsenDeterminer $functionCallNodeFqsenDeterminer,
        GlobalFunctionsProvider $globalFunctionsProvider
    ) {
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
        $this->globalFunctionsProvider = $globalFunctionsProvider;
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
            throw new LogicException('Expected function node, got ' . get_class($node) . ' instead');
        }

        $nameNode = new Node\Name\Relative($node->name);
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
    protected function getFunctionInfo(string $fullyQualifiedName): array
    {
        $functions = $this->globalFunctionsProvider->getAll();

        if (!isset($functions[$fullyQualifiedName])) {
            throw new UnexpectedValueException('No data found for function with name ' . $fullyQualifiedName);
        }

        return $functions[$fullyQualifiedName];
    }
}
