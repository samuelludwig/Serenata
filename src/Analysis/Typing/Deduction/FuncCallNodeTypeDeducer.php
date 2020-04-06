<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use PhpParser\Node;

use Serenata\Analysis\Node\FunctionCallNodeFqsenDeterminer;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

use Serenata\Parsing\InvalidTypeNode;

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
     * @var FunctionCallNodeFqsenDeterminer
     */
    private $functionCallNodeFqsenDeterminer;

    /**
     * @param ManagerRegistry                 $managerRegistry
     * @param FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Expr\FuncCall) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        } elseif ($context->getNode()->name instanceof Node\Expr) {
            return new InvalidTypeNode(); // Can't currently deduce type of an expression such as "{$foo}()";
        }

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine(
            $context->getNode(),
            $context->getTextDocumentItem()->getUri(),
            $context->getPosition()
        );

        /** @var Structures\Function_|null $globalFunction */
        $globalFunction = $this->managerRegistry->getRepository(Structures\Function_::class)->findOneBy([
            'fqcn' => $fqsen,
        ]);

        if ($globalFunction === null) {
            return new InvalidTypeNode();
        }

        return $globalFunction->getReturnType();
    }
}
