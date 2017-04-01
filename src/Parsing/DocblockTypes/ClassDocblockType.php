<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents a class docblock type.
 *
 * {@inheritDoc}
 */
class ClassDocblockType extends SingleDocblockType
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->name;
    }
}
