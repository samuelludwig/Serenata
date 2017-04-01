<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents a docblock type.
 *
 * This is a value object and immutable.
 */
abstract class DocblockType
{
    /**
     * @return string
     */
    abstract public function toString(): string;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
