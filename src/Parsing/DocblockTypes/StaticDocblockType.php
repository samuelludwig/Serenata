<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents static as docblock type.
 *
 * {@inheritDoc}
 */
class StaticDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'static';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
