<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents an iterable as docblock type.
 *
 * {@inheritDoc}
 */
class IterableDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'iterable';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
