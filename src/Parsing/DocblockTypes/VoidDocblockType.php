<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents void as docblock type.
 *
 * {@inheritDoc}
 */
class VoidDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'void';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
