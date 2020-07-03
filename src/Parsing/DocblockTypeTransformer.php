<?php

namespace Serenata\Parsing;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;

/**
 * Transforms a tree of {@see TypeNode} objects into a tree of new objects by applying a transformation on them.
 */
final class DocblockTypeTransformer implements DocblockTypeTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform(TypeNode $docblockType, callable $transformer): TypeNode
    {
        $transformedType = $transformer($docblockType);

        if ($transformedType instanceof UnionTypeNode) {
            return new UnionTypeNode(array_map(function (TypeNode $type) use ($transformer): TypeNode {
                return $this->transform($type, $transformer);
            }, $transformedType->types));
        } elseif ($transformedType instanceof IntersectionTypeNode) {
            return new IntersectionTypeNode(array_map(function (TypeNode $type) use ($transformer): TypeNode {
                return $this->transform($type, $transformer);
            }, $transformedType->types));
        } elseif ($transformedType instanceof IntersectionTypeNode) {
            return new IntersectionTypeNode(array_map(function (TypeNode $type) use ($transformer): TypeNode {
                return $this->transform($type, $transformer);
            }, $transformedType->types));
        } elseif ($transformedType instanceof ArrayShapeNode) {
            return new ArrayShapeNode(array_map(function (TypeNode $type) use ($transformer): TypeNode {
                return $this->transform($type, $transformer);
            }, $transformedType->items));
        } elseif ($transformedType instanceof ArrayTypeNode) {
            return new ArrayTypeNode($this->transform($transformedType->type, $transformer));
        } elseif ($transformedType instanceof NullableTypeNode) {
            return new NullableTypeNode($this->transform($transformedType->type, $transformer));
        } elseif ($transformedType instanceof CallableTypeNode) {
            return new CallableTypeNode(
                $this->transform($transformedType->identifier, $transformer),
                // TODO: These parameters are not actual type nodes, but just nodes.
                $transformedType->parameters,
                $this->transform($transformedType->returnType, $transformer)
            );
        } elseif ($transformedType instanceof GenericTypeNode) {
            return new GenericTypeNode(
                $this->transform($transformedType->type, $transformer),
                array_map(function (TypeNode $type) use ($transformer): TypeNode {
                    return $this->transform($type, $transformer);
                }, $transformedType->genericTypes)
            );
        } elseif ($transformedType instanceof CallableTypeParameterNode) {
            return new CallableTypeParameterNode(
                $this->transform($transformedType->type, $transformer),
                $transformedType->isReference,
                $transformedType->isVariadic,
                $transformedType->parameterName,
                $transformedType->isOptional
            );
        } elseif ($transformedType instanceof ArrayShapeItemNode) {
            $keyName = $transformedType->keyName;

            if ($keyName instanceof TypeNode) {
                $keyName = $this->transform($keyName, $transformer);
            }

            return new ArrayShapeItemNode(
                $keyName,
                $transformedType->optional,
                $this->transform($transformedType->valueType, $transformer)
            );
        }

        return $transformedType;
    }
}
