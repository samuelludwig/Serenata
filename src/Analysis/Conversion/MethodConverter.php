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
     * @param Structures\Method $method
     * @param ArrayAccess       $class
     *
     * @return array
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
                'startLineMember' => $method->getRange()->getStart()->getLine() + 1,
                'endLineMember'   => $method->getRange()->getEnd()->getLine() + 1,
            ]
        ]);
    }
}
