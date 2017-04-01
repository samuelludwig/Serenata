<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents a resource as docblock type.
 *
 * {@inheritDoc}
 */
class ResourceDocblockType extends SpecialDocblockType
{
    /**
     * @var string
     */
    public const STRING_VALUE = 'resource';

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return self::STRING_VALUE;
    }
}
