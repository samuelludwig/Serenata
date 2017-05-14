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
     * @param Structures\Constant $constant
     * @param ArrayAccess         $class
     *
     * @return array
     */
    public function convertForClass(Structures\Constant $constant, ArrayAccess $class): array
    {
        $data = parent::convert($constant);

        return array_merge($data, [
            'declaringClass' => [
                'name'      => $class['name'],
                'filename'  => $class['filename'],
                'startLine' => $class['startLine'],
                'endLine'   => $class['endLine'],
                'type'      => $class['type']
            ],

            'declaringStructure' => [
                'name'            => $class['name'],
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
