<?php

namespace PhpIntegrator\Analysis\Conversion;

use ArrayAccess;

use PhpIntegrator\Indexing\Structures;

/**
 * Converts raw method data from the index to more useful data.
 */
class MethodConverter extends FunctionConverter
{
    /**
     * @param Structures\Function_ $method
     * @param ArrayAccess          $class
     *
     * @return array
     */
    public function convertForClass(Structures\Function_ $method, ArrayAccess $class): array
    {
        $data = parent::convert($method);

        return array_merge($data, [
            'isMagic'         => $method->getIsMagic(),
            'isPublic'        => $method->getAccessModifier()->getName() === 'public',
            'isProtected'     => $method->getAccessModifier()->getName() === 'protected',
            'isPrivate'       => $method->getAccessModifier()->getName() === 'private',
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
                'startLineMember' => $method->getStartLine(),
                'endLineMember'   => $method->getEndLine()
            ]
        ]);
    }
}
