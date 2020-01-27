<?php

namespace Serenata\Analysis\Conversion;

use Serenata\DocblockTypeParser\DocblockType;
use Serenata\DocblockTypeParser\CompoundDocblockType;
use Serenata\DocblockTypeParser\DocblockTypeTransformer;

/**
 * Base class for converters.
 */
abstract class AbstractConverter
{
    /**
     * @param DocblockType $type
     *
     * @return array[]
     */
    protected function convertDocblockType(DocblockType $type): array
    {
        $types = [];

        $docblockTypeTransformer = new DocblockTypeTransformer();
        $docblockTypeTransformer->transform($type, function (DocblockType $type) use (&$types): DocblockType {
            if (!$type instanceof CompoundDocblockType) {
                $types[] = [
                    'type'         => $type->toString(),
                    'resolvedType' => $type->toString(),
                ];
            }

            return $type;
        });

        return $types;
    }
}
