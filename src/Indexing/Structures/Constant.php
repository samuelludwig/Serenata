<?php

namespace PhpIntegrator\Indexing\Structures;

use Ramsey\Uuid\Uuid;

/**
 * Represents a constant.
 */
class Constant
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
     * @var string|null
     */
    private $fqcn;

    /**
     * @var File
     */
    private $file;

    /**
     * @var int
     */
    private $startLine;

    /**
     * @var int
     */
    private $endLine;

    /**
     * @var string
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $isDeprecated;

    /**
     * @var bool
     */
    private $isBuiltin;

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
     * @var TypeInfo[]
     */
    private $types;

    /**
     * @var Structure|null
     */
    private $structure;

    /**
     * @var AccessModifier|null
     */
    private $accessModifier;

    /**
     * @param string              $name
     * @param string|null         $fqcn
     * @param File                $file
     * @param int                 $startLine
     * @param int                 $endLine
     * @param string              $defaultValue
     * @param bool                $isDeprecated
     * @param bool                $isBuiltin
     * @param bool                $hasDocblock
     * @param string|null         $shortDescription
     * @param string|null         $longDescription
     * @param string|null         $typeDescription
     * @param TypeInfo[]          $types
     * @param Structure|null      $structure
     * @param AccessModifier|null $accessModifier
     */
    public function __construct(
        string $name,
        ?string $fqcn,
        File $file,
        int $startLine,
        int $endLine,
        string $defaultValue,
        bool $isDeprecated,
        bool $isBuiltin,
        bool $hasDocblock,
        ?string $shortDescription,
        ?string $longDescription,
        ?string $typeDescription,
        array $types,
        ?Structure $structure,
        ?AccessModifier $accessModifier
    ) {
        $this->id = (string) Uuid::uuid4();
        $this->name = $name;
        $this->fqcn = $fqcn;
        $this->file = $file;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->defaultValue = $defaultValue;
        $this->isDeprecated = $isDeprecated;
        $this->isBuiltin = $isBuiltin;
        $this->hasDocblock = $hasDocblock;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->typeDescription = $typeDescription;
        $this->types = $types;
        $this->structure = $structure;
        $this->accessModifier = $accessModifier;

        if ($structure) {
            $structure->addConstant($this);
        }
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
     * @return string|null
     */
    public function getFqcn(): ?string
    {
        return $this->fqcn;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getStartLine(): int
    {
        return $this->startLine;
    }

    /**
     * @return int
     */
    public function getEndLine(): int
    {
        return $this->endLine;
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
    public function getIsBuiltin(): bool
    {
        return $this->isBuiltin;
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
     * @return TypeInfo[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return Structure|null
     */
    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    /**
     * @return AccessModifier|null
     */
    public function getAccessModifier(): ?AccessModifier
    {
        return $this->accessModifier;
    }
}
