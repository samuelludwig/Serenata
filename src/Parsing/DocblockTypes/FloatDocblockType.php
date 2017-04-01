<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents a float as docblock type.
 *
 * {@inheritDoc}
 */
class FloatDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'float';

    /**
     * @var string
     */
    public const STRING_VALUE_ALIAS = 'double';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
