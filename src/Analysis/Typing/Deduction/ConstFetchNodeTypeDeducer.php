<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Analysis\Conversion\ConstantConverter;

use Serenata\Common\FilePosition;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Utility\NodeHelpers;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ConstFetch} node.
 */
final class ConstFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var ConstantConverter
     */
    private $constantConverter;

    /**
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param ManagerRegistry                            $managerRegistry
     * @param ConstantConverter                          $constantConverter
     */
    public function __construct(
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        ManagerRegistry $managerRegistry,
        ConstantConverter $constantConverter
    ) {
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->managerRegistry = $managerRegistry;
        $this->constantConverter = $constantConverter;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Expr\ConstFetch) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $name = NodeHelpers::fetchClassName($context->getNode()->name);

        if ($name === 'null') {
            return ['null'];
        } elseif ($name === 'true' || $name === 'false') {
            return ['bool'];
        }

        $filePosition = new FilePosition(
            $context->getTextDocumentItem()->getUri(),
            $context->getPosition()
        );

        $fqsen = $this->structureAwareNameResolverFactory->create($filePosition)->resolve($name, $filePosition);

        /** @var Structures\ConstantLike|null $globalConstant */
        $globalConstant = $this->managerRegistry->getRepository(Structures\Constant::class)->findOneBy([
            'fqcn' => $fqsen
        ]);

        if (!$globalConstant) {
            return [];
        }

        $convertedGlobalConstant = $this->constantConverter->convert($globalConstant);

        return $this->fetchResolvedTypesFromTypeArrays($convertedGlobalConstant['types']);
    }
}
