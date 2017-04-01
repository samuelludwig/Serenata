<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents $this as docblock type.
 *
 * {@inheritDoc}
 */
class ThisDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = '$this';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
