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
     * FQCN of the type after it has been resolved.
     *
     * For example, if the type is "static", the FQCN won't hold anything useful. The actual type must be determined
     * based on its context, such as what class we are in, is the method inherited, and so on.
     *
     * @var string
     */
    private $resolvedType;

    /**
     * @param string      $type
     * @param string      $fqcn
     * @param string|null $resolvedType
     */
    public function __construct(string $type, string $fqcn, ?string $resolvedType = null)
    {
        $this->type = $type;
        $this->fqcn = $fqcn;
        $this->resolvedType = $resolvedType;
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

    /**
     * @return string
     */
    public function getResolvedType(): string
    {
        if ($this->resolvedType === null) {
            throw new LogicException('Resolved type has not been determined yet');
        }

        return $this->resolvedType;
    }

    /**
     * @param string $resolvedType
     *
     * @return void
     */
    public function setResolvedType(string $resolvedType): void
    {
        $this->resolvedType = $resolvedType;
    }
}
