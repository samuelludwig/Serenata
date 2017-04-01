<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents null as docblock type.
 *
 * {@inheritDoc}
 */
class NullDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'null';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
