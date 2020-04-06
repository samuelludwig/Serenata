<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\FilePositionClasslikeDeterminer;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;

use Serenata\Analysis\Typing\TypeNormalizerInterface;

use Serenata\Common\FilePosition;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Parsing\InvalidTypeNode;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

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
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Name) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $nameString = NodeHelpers::fetchClassName($context->getNode());

        if ($nameString === SpecialDocblockTypeIdentifierLiteral::STATIC_ ||
            $nameString === SpecialDocblockTypeIdentifierLiteral::SELF_
        ) {
            $currentClass = $this->filePositionClasslikeDeterminer->determine(
                $context->getTextDocumentItem(),
                $context->getPosition()
            );

            if ($currentClass === null) {
                return new InvalidTypeNode();
            }

            return new IdentifierTypeNode($this->typeNormalizer->getNormalizedFqcn($currentClass));
        } elseif ($nameString === SpecialDocblockTypeIdentifierLiteral::PARENT_) {
            $currentClassName = $this->filePositionClasslikeDeterminer->determine(
                $context->getTextDocumentItem(),
                $context->getPosition()
            );

            if ($currentClassName === null) {
                return new InvalidTypeNode();
            }

            $classInfo = $this->classlikeInfoBuilder->build($currentClassName);

            if (count($classInfo['parents']) === 0) {
                return new InvalidTypeNode();
            }

            $type = $classInfo['parents'][0];

            return new IdentifierTypeNode($this->typeNormalizer->getNormalizedFqcn($type));
        }

        $filePosition = new FilePosition(
            $context->getTextDocumentItem()->getUri(),
            $context->getPosition()
        );

        return new IdentifierTypeNode(
            $this->structureAwareNameResolverFactory->create($filePosition)->resolve($nameString, $filePosition)
        );
    }
}
