<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents an array as docblock type.
 *
 * {@inheritDoc}
 */
class ArrayDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'array';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
