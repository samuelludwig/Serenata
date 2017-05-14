<?php

namespace PhpIntegrator\Analysis\Conversion;

use ArrayAccess;

use PhpIntegrator\Indexing\Structures;

/**
 * Converts raw property data from the index to more useful data.
 */
class PropertyConverter extends AbstractConverter
{
    /**
     * @param Structures\Property $property
     * @param ArrayAccess         $class
     *
     * @return array
     */
    public function convertForClass(Structures\Property $property, ArrayAccess $class): array
    {
        $data = [
            'name'               => $property->getName(),
            'startLine'          => $property->getStartLine(),
            'endLine'            => $property->getEndLine(),
            'defaultValue'       => $property->getDefaultValue(),
            'isMagic'            => $property->getIsMagic(),
            'isPublic'           => $property->getAccessModifier()->getName() === 'public',
            'isProtected'        => $property->getAccessModifier()->getName() === 'protected',
            'isPrivate'          => $property->getAccessModifier()->getName() === 'private',
            'isStatic'           => $property->getIsStatic(),
            'isDeprecated'       => $property->getIsDeprecated(),
            'hasDocblock'        => $property->getHasDocblock(),
            'hasDocumentation'   => $property->getHasDocblock(),

            'shortDescription'  => $property->getShortDescription(),
            'longDescription'   => $property->getLongDescription(),
            'typeDescription'   => $property->getTypeDescription(),

            'types'             => $this->convertTypes($property->getTypes()),
        ];

        return array_merge($data, [
            'override'          => null,

            'declaringClass' => [
                'name'            => $class['name'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type'],
            ],

            'declaringStructure' => [
                'name'            => $class['name'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type'],
                'startLineMember' => $property->getStartLine(),
                'endLineMember'   => $property->getEndLine()
            ]
        ]);
    }
}
