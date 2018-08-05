<?php

namespace Serenata\Analysis\Typing;

use Closure;

use Serenata\Common\FilePosition;

use Serenata\DocblockTypeParser\DocblockType;
use Serenata\DocblockTypeParser\ClassDocblockType;
use Serenata\DocblockTypeParser\DocblockTypeTransformerInterface;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

/**
 * Transforms {@see DocblockType}s by resolving names into FQCN's.
 */
final class TypeResolvingDocblockTypeTransformer
{
    /**
     * @var DocblockTypeTransformerInterface
     */
    private $docblockTypeTransformer;

    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @param DocblockTypeTransformerInterface           $docblockTypeTransformer
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     */
    public function __construct(
        DocblockTypeTransformerInterface $docblockTypeTransformer,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
    ) {
        $this->docblockTypeTransformer = $docblockTypeTransformer;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
    }

    /**
     * @param DocblockType $type
     * @param FilePosition $filePosition
     *
     * @return DocblockType
     */
    public function resolve(DocblockType $type, FilePosition $filePosition): DocblockType
    {
        return $this->docblockTypeTransformer->transform($type, $this->createTransformer($filePosition));
    }

    /**
     * @param FilePosition $filePosition
     *
     * @return Closure
     */
    private function createTransformer(FilePosition $filePosition): Closure
    {
        $positionalNameResolver = $this->structureAwareNameResolverFactory->create($filePosition);

        return function (DocblockType $type) use ($positionalNameResolver, $filePosition): DocblockType {
            if ($type instanceof ClassDocblockType) {
                return new ClassDocblockType($positionalNameResolver->resolve($type->getName(), $filePosition));
            }

            return $type;
        };
    }
}
