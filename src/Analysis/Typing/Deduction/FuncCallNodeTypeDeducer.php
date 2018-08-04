<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use Serenata\Analysis\Conversion\FunctionConverter;

use Serenata\Analysis\Node\FunctionCallNodeFqsenDeterminer;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

use PhpParser\Node;

use Serenata\Utility\PositionEncoding;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\FuncCall} node.
 */
final class FuncCallNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var FunctionConverter
     */
    private $functionConverter;

    /**
     * @var FunctionCallNodeFqsenDeterminer
     */
    private $functionCallNodeFqsenDeterminer;

    /**
     * @param ManagerRegistry                 $managerRegistry
     * @param FunctionConverter               $functionConverter
     * @param FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        FunctionConverter $functionConverter,
        FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->functionConverter = $functionConverter;
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function deduce(Node $node, Structures\File $file, string $code, int $offset): array
    {
        if (!$node instanceof Node\Expr\FuncCall) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromFuncCallNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\FuncCall $node
     * @param Structures\File    $file
     * @param string             $code
     * @param int                $offset
     *
     * @return array
     */
    private function deduceTypesFromFuncCallNode(
        Node\Expr\FuncCall $node,
        Structures\File $file,
        string $code,
        int $offset
    ): array {
        if ($node->name instanceof Node\Expr) {
            return []; // Can't currently deduce type of an expression such as "{$foo}()";
        }

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine($node, $file, Position::createFromByteOffset(
            $offset,
            $code,
            PositionEncoding::VALUE
        ));

        /** @var Structures\Function_|null $globalFunction */
        $globalFunction = $this->managerRegistry->getRepository(Structures\Function_::class)->findOneBy([
            'fqcn' => $fqsen
        ]);

        if (!$globalFunction) {
            return [];
        }

        $convertedGlobalFunction = $this->functionConverter->convert($globalFunction);

        return $this->fetchResolvedTypesFromTypeArrays($convertedGlobalFunction['returnTypes']);
    }
}
