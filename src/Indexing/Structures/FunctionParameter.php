<?php

namespace Serenata\Indexing\Structures;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Represents a function parameter.
 *
 * @final
 */
class FunctionParameter extends FunctionLikeParameter
{
    /**
     * @var Function_
     */
    private $function;

    /**
     * @param Function_   $function
     * @param string      $name
     * @param string|null $typeHint
     * @param TypeNode    $type
     * @param string|null $description
     * @param string|null $defaultValue
     * @param bool        $isReference
     * @param bool        $isOptional
     * @param bool        $isVariadic
     */
    public function __construct(
        Function_ $function,
        string $name,
        ?string $typeHint,
        TypeNode $type,
        ?string $description,
        ?string $defaultValue,
        bool $isReference,
        bool $isOptional,
        bool $isVariadic
    ) {
        $this->id = uniqid('', true);
        $this->function = $function;
        $this->name = $name;
        $this->typeHint = $typeHint;
        $this->type = $type;
        $this->description = $description;
        $this->defaultValue = $defaultValue;
        $this->isReference = $isReference;
        $this->isOptional = $isOptional;
        $this->isVariadic = $isVariadic;

        $this->function->addParameter($this);
    }

    /**
     * @return Function_
     */
    public function getFunction(): Function_
    {
        return $this->function;
    }
}
