<?php

namespace Serenata\Analysis\Conversion;

use ArrayAccess;

use Serenata\Indexing\Structures;

use Serenata\Indexing\Structures\AccessModifierNameValue;

/**
 * Converts raw property data from the index to more useful data.
 */
final class PropertyConverter extends AbstractConverter
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
            // TODO: "+ 1" is only done for backwards compatibility, remove as soon as we can break it.
            'startLine'          => $property->getRange()->getStart()->getLine() + 1,
            'endLine'            => $property->getRange()->getEnd()->getLine() + 1,
            'filename'           => $property->getFile()->getPath(),
            'defaultValue'       => $property->getDefaultValue(),
            'isMagic'            => $property->getIsMagic(),
            'isPublic'           => $property->getAccessModifier()->getName() === AccessModifierNameValue::PUBLIC_,
            'isProtected'        => $property->getAccessModifier()->getName() === AccessModifierNameValue::PROTECTED_,
            'isPrivate'          => $property->getAccessModifier()->getName() === AccessModifierNameValue::PRIVATE_,
            'isStatic'           => $property->getIsStatic(),
            'isDeprecated'       => $property->getIsDeprecated(),
            'hasDocblock'        => $property->getHasDocblock(),
            'hasDocumentation'   => $property->getHasDocblock(),

            'shortDescription'  => $property->getShortDescription(),
            'longDescription'   => $property->getLongDescription(),
            'typeDescription'   => $property->getTypeDescription(),

            'types'             => $this->convertDocblockType($property->getType()),
        ];

        return array_merge($data, [
            'override'          => null,

            'declaringClass' => [
                'fqcn'            => $class['fqcn'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type'],
            ],

            'declaringStructure' => [
                'fqcn'            => $class['fqcn'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type'],
                // TODO: "+ 1" is only done for backwards compatibility, remove as soon as we can break it.
                'startLineMember' => $property->getRange()->getStart()->getLine() + 1,
                'endLineMember'   => $property->getRange()->getEnd()->getLine() + 1,
            ],
        ]);
    }
}
