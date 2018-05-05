<?php

namespace Serenata\Analysis\Conversion;

use Serenata\Indexing\Structures;

/**
 * Base class for converters.
 */
abstract class AbstractConverter
{
    /**
     * @param Structures\TypeInfo[] $rawTypes
     *
     * @return array[]
     */
    protected function convertTypes(array $rawTypes): array
    {
        $types = [];

        foreach ($rawTypes as $rawType) {
            $types[] = [
                'type'         => $rawType->getType(),
                'fqcn'         => $rawType->getFqcn(),
                'resolvedType' => $rawType->getFqcn()
            ];
        }

        return $types;
    }
}
