<?php

namespace Serenata\Analysis\Conversion;

use ArrayAccess;

use Serenata\Indexing\Structures;

use Serenata\Indexing\Structures\AccessModifierNameValue;

/**
 * Converts raw class constant data from the index to more useful data.
 */
final class ClasslikeConstantConverter
{
    /**
     * @var ConstantConverter
     */
    private $constantConverter;

    /**
     * @param ConstantConverter $constantConverter
     */
    public function __construct(ConstantConverter $constantConverter)
    {
        $this->constantConverter = $constantConverter;
    }

    /**
     * @param Structures\ClassConstant $constant
     * @param ArrayAccess              $class
     *
     * @return array
     */
    public function convertForClass(Structures\ClassConstant $constant, ArrayAccess $class): array
    {
        return array_merge($this->constantConverter->convert($constant), [
            'isPublic' => $constant->getAccessModifier() ?
                $constant->getAccessModifier()->getName() === AccessModifierNameValue::PUBLIC_ : true,

            'isProtected' => $constant->getAccessModifier() ?
                $constant->getAccessModifier()->getName() === AccessModifierNameValue::PROTECTED_ : false,

            'isPrivate' => $constant->getAccessModifier() ?
                $constant->getAccessModifier()->getName() === AccessModifierNameValue::PRIVATE_ : false,

            'declaringClass' => [
                'fqcn'      => $class['fqcn'],
                'uri'       => $class['uri'],
                'range'     => $class['range'],
                'type'      => $class['type'],
            ],

            'declaringStructure' => [
                'fqcn'        => $class['fqcn'],
                'uri'         => $class['uri'],
                'range'       => $class['range'],
                'type'        => $class['type'],
                'memberRange' => $constant->getRange(),
            ],
        ]);
    }
}
