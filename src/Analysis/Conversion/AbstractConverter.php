<?php

namespace Serenata\Analysis\Conversion;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;

/**
 * Base class for converters.
 */
abstract class AbstractConverter
{
    /**
     * @param TypeNode $type
     *
     * @return array[]
     */
    protected function convertDocblockType(TypeNode $type): array
    {
        if ($type instanceof UnionTypeNode || $type instanceof IntersectionTypeNode) {
            return array_merge(...array_map(function (TypeNode $nestedType): array {
                return $this->convertDocblockType($nestedType);
            }, $type->types));
        }

        return [[
            'type'         => (string) $type,
            'resolvedType' => (string) $type,
        ]];
    }
}
