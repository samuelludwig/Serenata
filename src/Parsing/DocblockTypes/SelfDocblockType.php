<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents self as docblock type.
 *
 * {@inheritDoc}
 */
class SelfDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'self';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
