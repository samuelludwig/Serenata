<?php

namespace Serenata\Analysis\Conversion;

use ArrayAccess;

use Serenata\Indexing\Structures;

use Serenata\Indexing\Structures\AccessModifierNameValue;

/**
 * Converts raw method data from the index to more useful data.
 */
final class MethodConverter extends FunctionConverter
{
    /**
     * @param Structures\Method         $method
     * @param ArrayAccess<string,mixed> $class
     *
     * @return array<string,mixed>
     */
    public function convertForClass(Structures\Method $method, ArrayAccess $class): array
    {
        $data = parent::convert($method);

        return array_merge($data, [
            'isMagic'         => $method->getIsMagic(),
            'isPublic'        => $method->getAccessModifier()->getName() === AccessModifierNameValue::PUBLIC_,
            'isProtected'     => $method->getAccessModifier()->getName() === AccessModifierNameValue::PROTECTED_,
            'isPrivate'       => $method->getAccessModifier()->getName() === AccessModifierNameValue::PRIVATE_,
            'isStatic'        => $method->getIsStatic(),
            'isAbstract'      => $method->getIsAbstract(),
            'isFinal'         => $method->getIsFinal(),

            'override'        => null,
            'implementations' => [],

            'declaringClass' => [
                'fqcn'            => $class['fqcn'],
                'uri'             => $class['uri'],
                'range'           => $class['range'],
                'type'            => $class['type'],
            ],

            'declaringStructure' => [
                'fqcn'            => $class['fqcn'],
                'uri'             => $class['uri'],
                'range'           => $class['range'],
                'type'            => $class['type'],
                'memberRange'     => $method->getRange(),
            ],
        ]);
    }
}
