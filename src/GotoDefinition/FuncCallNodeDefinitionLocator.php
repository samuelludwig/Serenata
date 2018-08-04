<?php

namespace Serenata\GotoDefinition;

use UnexpectedValueException;

use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Analysis\Node\FunctionCallNodeFqsenDeterminer;

use PhpParser\Node;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Utility\PositionEncoding;

/**
 * Locates the definition of the function called in {@see Node\Expr\FuncCall} nodes.
 */
class FuncCallNodeDefinitionLocator
{
    /**
     * @var FunctionCallNodeFqsenDeterminer
     */
    private $functionCallNodeFqsenDeterminer;

    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @param FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     * @param FunctionListProviderInterface   $functionListProvider
     */
    public function __construct(
        FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer,
        FunctionListProviderInterface $functionListProvider
    ) {
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
        $this->functionListProvider = $functionListProvider;
    }

    /**
     * @param Node\Expr\FuncCall $node
     * @param Structures\File    $file
     * @param string             $code
     * @param int                $offset
     *
     * @throws UnexpectedValueException when the function was not found.
     * @throws UnexpectedValueException when a dynamic function call is passed.
     *
     * @return GotoDefinitionResult
     */
    public function locate(
        Node\Expr\FuncCall $node,
        Structures\File $file,
        string $code,
        int $offset
    ): GotoDefinitionResult {
        if (!$node->name instanceof Node\Name) {
            throw new UnexpectedValueException('Fetching FQSEN of dynamic function calls is not supported');
        }

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine($node, $file, Position::createFromByteOffset(
            $offset,
            $code,
            PositionEncoding::VALUE
        ));

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
