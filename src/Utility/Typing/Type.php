<?php

namespace PhpIntegrator\Utility\Typing;

/**
 * Represents a (parameter, property, constant) type.
 *
 * This is a value object and immutable.
 */
class Type
{
    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
