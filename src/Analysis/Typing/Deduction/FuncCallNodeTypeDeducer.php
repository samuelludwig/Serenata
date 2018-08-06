<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Analysis\Conversion\FunctionConverter;

use Serenata\Analysis\Node\FunctionCallNodeFqsenDeterminer;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

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
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Expr\FuncCall) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        } elseif ($context->getNode()->name instanceof Node\Expr) {
            return []; // Can't currently deduce type of an expression such as "{$foo}()";
        }

        $fqsen = $this->functionCallNodeFqsenDeterminer->determine(
            $context->getNode(),
            $context->getTextDocumentItem()->getUri(),
            $context->getPosition()
        );

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
