<?php

namespace Serenata\Parsing;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;

/**
 * Extracts the top-level types of a type node.
 */
final class ToplevelTypeExtractor implements ToplevelTypeExtractorInterface
{
    /**
     * @inheritDoc
     */
    public function extract(TypeNode $type): array
    {
        if ($type instanceof UnionTypeNode || $type instanceof IntersectionTypeNode) {
            return array_merge(...array_map(function (TypeNode $nestedType): array {
                return $this->extract($nestedType);
            }, $type->types));
        }

        return [$type];
    }
}
