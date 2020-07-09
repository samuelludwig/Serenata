<?php

namespace Serenata\Analysis\Conversion;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use Serenata\Parsing\ToplevelTypeExtractor;

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
        $typeExtractor = new ToplevelTypeExtractor();

        return array_map(function (TypeNode $nestedType): array {
            return [
                'type'         => (string) $nestedType,
                'resolvedType' => (string) $nestedType,
            ];
        }, $typeExtractor->extract($type));
    }
}
