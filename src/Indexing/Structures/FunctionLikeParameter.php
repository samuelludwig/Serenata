<?php

namespace Serenata\Indexing\Structures;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Represents a function-like parameter.
 */
abstract class FunctionLikeParameter
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $typeHint;

    /**
     * @var TypeNode
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $defaultValue;

    /**
     * @var bool
     */
    protected $isReference;

    /**
     * @var bool
     */
    protected $isOptional;

    /**
     * @var bool
     */
    protected $isVariadic;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getTypeHint(): ?string
    {
        return $this->typeHint;
    }

    /**
     * @return TypeNode
     */
    public function getType(): TypeNode
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * @return bool
     */
    public function getIsReference(): bool
    {
        return $this->isReference;
    }

    /**
     * @return bool
     */
    public function getIsOptional(): bool
    {
        return $this->isOptional;
    }

    /**
     * @return bool
     */
    public function getIsVariadic(): bool
    {
        return $this->isVariadic;
    }
}
