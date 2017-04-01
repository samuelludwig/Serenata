<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Represents an array docblock type.
 *
 * {@inheritDoc}
 */
class SpecializedArrayDocblockType extends ArrayDocblockType
{
    /**
     * @var DocblockType
     */
    private $type;

    /**
     * @param DocblockType $type
     */
    public function __construct(DocblockType $type)
    {
        $this->type = $type;
    }

    /**
     * @return DocblockType
     */
    public function getType(): DocblockType
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->type->toString() . '[]';
    }
}
