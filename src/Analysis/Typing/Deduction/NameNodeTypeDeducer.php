<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\FilePositionClasslikeDeterminer;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;

use Serenata\Analysis\Typing\TypeNormalizerInterface;

use Serenata\Common\FilePosition;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Utility\NodeHelpers;

/**
 * Type deducer that can deduce the type of a {@see Node\Name} node.
 */
final class NameNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var TypeNormalizerInterface
     */
    private $typeNormalizer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @var FilePositionClasslikeDeterminer
     */
    private $filePositionClasslikeDeterminer;

    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @param TypeNormalizerInterface                    $typeNormalizer
     * @param ClasslikeInfoBuilderInterface              $classlikeInfoBuilder
     * @param FilePositionClasslikeDeterminer            $filePositionClasslikeDeterminer
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     */
    public function __construct(
        TypeNormalizerInterface $typeNormalizer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        FilePositionClasslikeDeterminer $filePositionClasslikeDeterminer,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
    ) {
        $this->typeNormalizer = $typeNormalizer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->filePositionClasslikeDeterminer = $filePositionClasslikeDeterminer;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Name) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $nameString = NodeHelpers::fetchClassName($context->getNode());

        if ($nameString === 'static' || $nameString === 'self') {
            $currentClass = $this->filePositionClasslikeDeterminer->determine(
                $context->getTextDocumentItem(),
                $context->getPosition()
            );

            if ($currentClass === null) {
                return [];
            }

            return [$this->typeNormalizer->getNormalizedFqcn($currentClass)];
        } elseif ($nameString === 'parent') {
            $currentClassName = $this->filePositionClasslikeDeterminer->determine(
                $context->getTextDocumentItem(),
                $context->getPosition()
            );

            if (!$currentClassName) {
                return [];
            }

            $classInfo = $this->classlikeInfoBuilder->build($currentClassName);

            if (!$classInfo || count($classInfo['parents']) === 0) {
                return [];
            }

            $type = $classInfo['parents'][0];

            return [$this->typeNormalizer->getNormalizedFqcn($type)];
        }

        $filePosition = new FilePosition(
            $context->getTextDocumentItem()->getUri(),
            $context->getPosition()
        );

        $fqcn = $this->structureAwareNameResolverFactory->create($filePosition)->resolve($nameString, $filePosition);

        return [$fqcn];
    }
}
