<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents an object as docblock type.
 *
 * {@inheritDoc}
 */
class ObjectDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'object';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
