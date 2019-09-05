<?php

namespace Serenata\Indexing\Structures;

use Serenata\Common\Range;

use Serenata\DocblockTypeParser\DocblockType;

/**
 * Represents a property.
 *
 * @final
 */
class Property
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Range
     */
    private $range;

    /**
     * @var string|null
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $isDeprecated;

    /**
     * @var bool
     */
    private $isMagic;

    /**
     * @var bool
     */
    private $isStatic;

    /**
     * @var bool
     */
    private $hasDocblock;

    /**
     * @var string|null
     */
    private $shortDescription;

    /**
     * @var string|null
     */
    private $longDescription;

    /**
     * @var string|null
     */
    private $typeDescription;

    /**
     * @var Classlike
     */
    private $classlike;

    /**
     * @var AccessModifier
     */
    private $accessModifier;

    /**
     * @var DocblockType
     */
    private $type;

    /**
     * @param string         $name
     * @param File           $file
     * @param Range          $range
     * @param string|null    $defaultValue
     * @param bool           $isDeprecated
     * @param bool           $isMagic
     * @param bool           $isStatic
     * @param bool           $hasDocblock
     * @param string|null    $shortDescription
     * @param string|null    $longDescription
     * @param string|null    $typeDescription
     * @param Classlike      $classlike
     * @param AccessModifier $accessModifier
     * @param DocblockType   $type
     */
    public function __construct(
        string $name,
        File $file,
        Range $range,
        ?string $defaultValue,
        bool $isDeprecated,
        bool $isMagic,
        bool $isStatic,
        bool $hasDocblock,
        ?string $shortDescription,
        ?string $longDescription,
        ?string $typeDescription,
        Classlike $classlike,
        AccessModifier $accessModifier,
        DocblockType $type
    ) {
        $this->id = uniqid('', true);
        $this->name = $name;
        $this->file = $file;
        $this->range = $range;
        $this->defaultValue = $defaultValue;
        $this->isDeprecated = $isDeprecated;
        $this->isMagic = $isMagic;
        $this->isStatic = $isStatic;
        $this->hasDocblock = $hasDocblock;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->typeDescription = $typeDescription;
        $this->classlike = $classlike;
        $this->accessModifier = $accessModifier;
        $this->type = $type;

        $classlike->addProperty($this);
    }

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
     * @return string|null
     */
    public function getDefaultValue(): ?string
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
    public function getIsMagic(): bool
    {
        return $this->isMagic;
    }

    /**
     * @return bool
     */
    public function getIsStatic(): bool
    {
        return $this->isStatic;
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
     * @return Classlike
     */
    public function getClasslike(): Classlike
    {
        return $this->classlike;
    }

    /**
     * @return AccessModifier
     */
    public function getAccessModifier(): AccessModifier
    {
        return $this->accessModifier;
    }

    /**
     * @return DocblockType
     */
    public function getType(): DocblockType
    {
        return $this->type;
    }
}
