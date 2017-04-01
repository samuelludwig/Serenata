<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents mixed as docblock type.
 *
 * {@inheritDoc}
 */
class MixedDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'mixed';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
