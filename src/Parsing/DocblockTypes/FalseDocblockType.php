<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents the boolean false as docblock type.
 *
 * {@inheritDoc}
 */
class FalseDocblockType extends BoolDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'false';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
