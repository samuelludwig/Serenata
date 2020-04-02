<?php

namespace Serenata\Analysis\Typing;

use Closure;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use Serenata\Common\FilePosition;

use Serenata\Parsing\DocblockTypeTransformerInterface;

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
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @param DocblockTypeTransformerInterface           $docblockTypeTransformer
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param TypeAnalyzer                               $typeAnalyzer
     */
    public function __construct(
        DocblockTypeTransformerInterface $docblockTypeTransformer,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        TypeAnalyzer $typeAnalyzer
    ) {
        $this->docblockTypeTransformer = $docblockTypeTransformer;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->typeAnalyzer = $typeAnalyzer;
    }

    /**
     * @param TypeNode     $type
     * @param FilePosition $filePosition
     *
     * @return TypeNode
     */
    public function resolve(TypeNode $type, FilePosition $filePosition): TypeNode
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

        return function (TypeNode $type) use ($positionalNameResolver, $filePosition): TypeNode {
            if ($type instanceof IdentifierTypeNode && $this->typeAnalyzer->isClassType((string) $type)) {
                return new IdentifierTypeNode($positionalNameResolver->resolve((string) $type, $filePosition));
            }

            return $type;
        };
    }
}
