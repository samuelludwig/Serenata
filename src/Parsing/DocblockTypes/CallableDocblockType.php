<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents a callable as docblock type.
 *
 * {@inheritDoc}
 */
class CallableDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'callable';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
