<?php

namespace Serenata\Indexing\Structures;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use Serenata\Common\Range;

/**
 * Represents a (global) constant.
 *
 * @final
 */
class Constant extends ConstantLike
{
    /**
     * @var string
     */
    private $fqcn;

    /**
     * @param string      $name
     * @param string      $fqcn
     * @param File        $file
     * @param Range       $range
     * @param string      $defaultValue
     * @param bool        $isDeprecated
     * @param bool        $hasDocblock
     * @param string|null $shortDescription
     * @param string|null $longDescription
     * @param string|null $typeDescription
     * @param TypeNode    $type
     */
    public function __construct(
        string $name,
        string $fqcn,
        File $file,
        Range $range,
        string $defaultValue,
        bool $isDeprecated,
        bool $hasDocblock,
        ?string $shortDescription,
        ?string $longDescription,
        ?string $typeDescription,
        TypeNode $type
    ) {
        $this->id = uniqid('', true);
        $this->name = $name;
        $this->fqcn = $fqcn;
        $this->file = $file;
        $this->range = $range;
        $this->defaultValue = $defaultValue;
        $this->isDeprecated = $isDeprecated;
        $this->hasDocblock = $hasDocblock;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->typeDescription = $typeDescription;
        $this->type = $type;

        $file->addConstant($this);
    }

    /**
     * @return string
     */
    public function getFqcn(): string
    {
        return $this->fqcn;
    }
}
