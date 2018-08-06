<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\TypeNormalizerInterface;
use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Common\FilePosition;

use Serenata\Indexing\StorageInterface;

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
     * @var FileClasslikeListProviderInterface
     */
    private $fileClasslikeListProvider;

    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param TypeNormalizerInterface                    $typeNormalizer
     * @param ClasslikeInfoBuilderInterface              $classlikeInfoBuilder
     * @param FileClasslikeListProviderInterface         $fileClasslikeListProvider
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param StorageInterface                           $storage
     */
    public function __construct(
        TypeNormalizerInterface $typeNormalizer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        FileClasslikeListProviderInterface $fileClasslikeListProvider,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        StorageInterface $storage
    ) {
        $this->typeNormalizer = $typeNormalizer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->fileClasslikeListProvider = $fileClasslikeListProvider;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->storage = $storage;
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
            $currentClass = $this->findCurrentClassAt($context);

            if ($currentClass === null) {
                return [];
            }

            return [$this->typeNormalizer->getNormalizedFqcn($currentClass)];
        } elseif ($nameString === 'parent') {
            $currentClassName = $this->findCurrentClassAt($context);

            if (!$currentClassName) {
                return [];
            }

            $classInfo = $this->classlikeInfoBuilder->build($currentClassName);

            if (!$classInfo || empty($classInfo['parents'])) {
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

    /**
     * @param \Serenata\Analysis\Typing\Deduction\TypeDeductionContext $context
     *
     * @return string|null
     */
    private function findCurrentClassAt(TypeDeductionContext $context): ?string
    {
        $position = $context->getPosition();
        $file = $this->storage->getFileByPath($context->getTextDocumentItem()->getUri());

        $bestMatch = null;

        /** @var string $fqcn */
        foreach ($this->fileClasslikeListProvider->getAllForFile($file) as $fqcn => $class) {
            if ($class['range']->contains($position)) {
                $bestMatch = $fqcn;
            }
        }

        return $bestMatch;
    }
}
