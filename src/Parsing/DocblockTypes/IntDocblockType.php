<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents an int as docblock type.
 *
 * {@inheritDoc}
 */
class IntDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'int';

    /**
     * @var string
     */
    public const STRING_VALUE_ALIAS = 'integer';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
