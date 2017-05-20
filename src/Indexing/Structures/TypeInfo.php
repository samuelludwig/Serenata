<?php

namespace PhpIntegrator\Indexing\Structures;

use LogicException;

/**
 * Represents a structure type.
 */
class TypeInfo
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $fqcn;

    /**
     * @param string $type
     * @param string $fqcn
     */
    public function __construct(string $type, string $fqcn)
    {
        $this->type = $type;
        $this->fqcn = $fqcn;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFqcn(): string
    {
        return $this->fqcn;
    }
}
