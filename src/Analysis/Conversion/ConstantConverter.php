<?php

namespace PhpIntegrator\Analysis\Conversion;

/**
 * Converts raw constant data from the index to more useful data.
 */
class ConstantConverter extends AbstractConverter
{
    /**
     * @param array $rawInfo
     *
     * @return array
     */
    public function convert(array $rawInfo): array
    {
        return [
            'name'              => $rawInfo['name'],
            'fqcn'              => $rawInfo['fqcn'],
            'isBuiltin'         => !!$rawInfo['is_builtin'],
            'startLine'         => (int) $rawInfo['start_line'],
            'endLine'           => (int) $rawInfo['end_line'],
            'defaultValue'      => $rawInfo['default_value'],
            'filename'          => $rawInfo['path'],

            'isPublic'          => (isset($rawInfo['access_modifier']) ? $rawInfo['access_modifier'] === 'public' : true),
            'isProtected'       => (isset($rawInfo['access_modifier']) ? $rawInfo['access_modifier'] === 'protected' : false),
            'isPrivate'         => (isset($rawInfo['access_modifier']) ? $rawInfo['access_modifier'] === 'private' : false),
            'isStatic'          => true,
            'isDeprecated'      => !!$rawInfo['is_deprecated'],
            'hasDocblock'       => !!$rawInfo['has_docblock'],
            'hasDocumentation'  => !!$rawInfo['has_docblock'],

            'shortDescription'  => $rawInfo['short_description'],
            'longDescription'   => $rawInfo['long_description'],
            'typeDescription'   => $rawInfo['type_description'],

            'types'             => $this->getReturnTypeDataForSerializedTypes($rawInfo['types_serialized'])
        ];
    }
}
