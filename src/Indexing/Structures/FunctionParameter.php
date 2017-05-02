<?php

namespace PhpIntegrator\Indexing\Structures;

use Ramsey\Uuid\Uuid;

/**
 * Represents a function parameter.
 */
class FunctionParameter
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Function_
     */
    private $function;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $typeHint;

    /**
     * @var array[]
     */
    private $types;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $isNullable;

    /**
     * @var bool
     */
    private $isReference;

    /**
     * @var bool
     */
    private $isOptional;

    /**
     * @var bool
     */
    private $isVariadic;

    /**
     * @param Function_   $function
     * @param string      $name
     * @param string|null $typeHint
     * @param array[]     $types
     * @param string|null $description
     * @param string|null $defaultValue
     * @param bool        $isNullable
     * @param bool        $isReference
     * @param bool        $isOptional
     * @param bool        $isVariadic
     */
    public function __construct(
        Function_ $function,
        string $name,
        string $typeHint = null,
        array $types,
        string $description = null,
        string $defaultValue = null,
        bool $isNullable,
        bool $isReference,
        bool $isOptional,
        bool $isVariadic
    ) {
        $this->id = Uuid::uuid4();
        $this->function = $function;
        $this->name = $name;
        $this->typeHint = $typeHint;
        $this->types = $types;
        $this->description = $description;
        $this->defaultValue = $defaultValue;
        $this->isNullable = $isNullable;
        $this->isReference = $isReference;
        $this->isOptional = $isOptional;
        $this->isVariadic = $isVariadic;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Function_
     */
    public function getFunction(): Function_
    {
        return $this->function;
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
     * @return array[]
     */
    public function getTypes(): array
    {
        return $this->types;
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
    public function getIsNullable(): bool
    {
        return $this->isNullable;
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
