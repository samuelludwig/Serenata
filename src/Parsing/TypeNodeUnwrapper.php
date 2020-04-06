<?php

namespace Serenata\Parsing;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;

/**
 * Unwraps type nodes if necessary.
 *
 * Compound type nodes with only one child are useless. This class strips them (only top-level).
 */
class TypeNodeUnwrapper
{
    /**
     * @param TypeNode $type
     *
     * @return TypeNode
     */
    public static function unwrap(TypeNode $type): TypeNode
    {
        if ($type instanceof UnionTypeNode || $type instanceof IntersectionTypeNode) {
            if (count($type->types) === 1) {
                return array_values($type->types)[0];
            }
        }

        return $type;
    }
}
