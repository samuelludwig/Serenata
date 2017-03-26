<?php

namespace PhpIntegrator\Utility\DocblockTyping;

/**
 * Represents an array docblock type.
 *
 * {@inheritDoc}
 */
class ArrayDocblockType extends SpecialDocblockType
{
    /**
     * @return DocblockType
     */
    public function getValueTypeFromArrayType(): DocblockType
    {
        $matches = [];

        if (preg_match(self::ARRAY_TYPE_HINT_REGEX, $this->toString(), $matches) === 1) {
            return static::createFromString($matches[1]);
        }

        return static::createFromString('mixed');
    }
}
