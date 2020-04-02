<?php

namespace Serenata\Indexing\Structures;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use Serenata\Common\Range;

/**
 * Contains common properties for constant-like structural elements.
 */
abstract class ConstantLike
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
     * @var File
     */
    protected $file;

    /**
     * @var Range
     */
    protected $range;

    /**
     * @var string
     */
    protected $defaultValue;

    /**
     * @var bool
     */
    protected $isDeprecated;

    /**
     * @var bool
     */
    protected $hasDocblock;

    /**
     * @var string|null
     */
    protected $shortDescription;

    /**
     * @var string|null
     */
    protected $longDescription;

    /**
     * @var string|null
     */
    protected $typeDescription;

    /**
     * @var TypeNode
     */
    protected $type;

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
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return Range
     */
    public function getRange(): Range
    {
        return $this->range;
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    /**
     * @return bool
     */
    public function getIsDeprecated(): bool
    {
        return $this->isDeprecated;
    }

    /**
     * @return bool
     */
    public function getHasDocblock(): bool
    {
        return $this->hasDocblock;
    }

    /**
     * @return string|null
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * @return string|null
     */
    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    /**
     * @return string|null
     */
    public function getTypeDescription(): ?string
    {
        return $this->typeDescription;
    }

    /**
     * @return TypeNode
     */
    public function getType(): TypeNode
    {
        return $this->type;
    }
}
