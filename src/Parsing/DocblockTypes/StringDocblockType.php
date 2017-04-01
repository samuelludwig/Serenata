<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents a string as docblock type.
 *
 * {@inheritDoc}
 */
class StringDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'string';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
