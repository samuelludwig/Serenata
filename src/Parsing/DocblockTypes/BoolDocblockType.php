<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents a boolean as docblock type.
 *
 * {@inheritDoc}
 */
class BoolDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'bool';

    /**
     * @var string
     */
    public const STRING_VALUE_ALIAS = 'boolean';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
