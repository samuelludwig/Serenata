<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents the boolean true as docblock type.
 *
 * {@inheritDoc}
 */
class TrueDocblockType extends BoolDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'true';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
