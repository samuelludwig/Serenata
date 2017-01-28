<?php

namespace PhpIntegrator\Analysis\Conversion;

/**
 * Base class for converters.
 */
abstract class AbstractConverter
{
    /**
     * @param string $serializedTypes
     *
     * @return array[]
     */
    protected function getReturnTypeDataForSerializedTypes(string $serializedTypes): array
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
