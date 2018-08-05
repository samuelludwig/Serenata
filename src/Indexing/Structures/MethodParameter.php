<?php

namespace Serenata\Indexing\Structures;

use Serenata\DocblockTypeParser\DocblockType;

/**
 * Represents a method parameter.
 */
class MethodParameter extends FunctionLikeParameter
{
    /**
     * @var Method
     */
    private $method;

    /**
     * @param Method       $method
     * @param string       $name
     * @param string|null  $typeHint
     * @param DocblockType $type
     * @param string|null  $description
     * @param string|null  $defaultValue
     * @param bool         $isReference
     * @param bool         $isOptional
     * @param bool         $isVariadic
     */
    public function __construct(
        Method $method,
        string $name,
        ?string $typeHint,
        DocblockType $type,
        ?string $description,
        ?string $defaultValue,
        bool $isReference,
        bool $isOptional,
        bool $isVariadic
    ) {
        $this->id = uniqid('', true);
        $this->method = $method;
        $this->name = $name;
        $this->typeHint = $typeHint;
        $this->type = $type;
        $this->description = $description;
        $this->defaultValue = $defaultValue;
        $this->isReference = $isReference;
        $this->isOptional = $isOptional;
        $this->isVariadic = $isVariadic;

        $this->method->addParameter($this);
    }

    /**
     * @return Method
     */
    public function getMethod(): Method
    {
        return $this->method;
    }
}
