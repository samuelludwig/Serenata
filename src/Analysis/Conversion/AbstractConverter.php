<?php

namespace PhpIntegrator\Analysis\Conversion;

/**
 * Base class for converters.
 */
abstract class AbstractConverter
{
    /**
     * @param array[] $serializedTypes
     *
     * @return array[]
     */
    protected function getReturnTypeDataForSerializedTypes(array $serializedTypes): array
    {
        $types = [];

        $rawTypes = unserialize($serializedTypes);

        foreach ($rawTypes as $rawType) {
            $types[] = [
                'type'         => $rawType['type'],
                'fqcn'         => $rawType['fqcn'],
                'resolvedType' => $rawType['fqcn']
            ];
        }

        return $types;
    }
}
