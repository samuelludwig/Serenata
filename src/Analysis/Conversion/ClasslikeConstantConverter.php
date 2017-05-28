<?php

namespace PhpIntegrator\Analysis\Conversion;

use ArrayAccess;

use PhpIntegrator\Indexing\Structures;

/**
 * Converts raw class constant data from the index to more useful data.
 */
class ClasslikeConstantConverter extends ConstantConverter
{
    /**
     * @param Structures\ClassConstant $constant
     * @param ArrayAccess              $class
     *
     * @return array
     */
    public function convertForClass(Structures\ClassConstant $constant, ArrayAccess $class): array
    {
        $data = parent::convert($constant);

        return array_merge($data, [
            'isPublic'          => $constant->getAccessModifier() ? $constant->getAccessModifier()->getName() === 'public' : true,
            'isProtected'       => $constant->getAccessModifier() ? $constant->getAccessModifier()->getName() === 'protected' : false,
            'isPrivate'         => $constant->getAccessModifier() ? $constant->getAccessModifier()->getName() === 'private' : false,

            'declaringClass' => [
                'fqcn'      => $class['fqcn'],
                'filename'  => $class['filename'],
                'startLine' => $class['startLine'],
                'endLine'   => $class['endLine'],
                'type'      => $class['type']
            ],

            'declaringStructure' => [
                'fqcn'            => $class['fqcn'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type'],
                'startLineMember' => $constant->getStartLine(),
                'endLineMember'   => $constant->getEndLine()
            ]
        ]);
    }
}
