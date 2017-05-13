<?php

namespace PhpIntegrator\Indexing\Structures;

use Doctrine\Common\Collections\ArrayCollection;

use Ramsey\Uuid\Uuid;

/**
 * Represents a structure or classlike.
 */
class Structure
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
     * @var string
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
     * @var StructureType
     */
    private $type;

    /**
     * @var string|null
     */
    private $shortDescription;

    /**
     * @var string|null
     */
    private $longDescription;

    /**
     * @var bool
     */
    private $isBuiltin;

    /**
     * @var bool
     */
    private $isAbstract;

    /**
     * @var bool
     */
    private $isFinal;

    /**
     * @var bool
     */
    private $isAnnotation;

    /**
     * @var bool
     */
    private $isDeprecated;

    /**
     * @var bool
     */
    private $hasDocblock;

    /**
     * @var ArrayCollection
     */
    private $parents;

    /**
     * @var ArrayCollection
     */
    private $children;

    /**
     * @var ArrayCollection
     */
    private $interfaces;

    /**
     * @var ArrayCollection
     */
    private $implementors;

    /**
     * @var ArrayCollection
     */
    private $traits;

    /**
     * @var ArrayCollection
     */
    private $traitUsers;

    /**
     * @var ArrayCollection
     */
    private $traitAliases;

    /**
     * @var ArrayCollection
     */
    private $traitPrecedences;

    /**
     * @var ArrayCollection
     */
    private $constants;

    /**
     * @var ArrayCollection
     */
    private $properties;

    /**
     * @var ArrayCollection
     */
    private $methods;

    /**
     * @param string                     $name
     * @param string                     $fqcn
     * @param File                       $file
     * @param int                        $startLine
     * @param int                        $endLine
     * @param StructureType              $type
     * @param string|null                $shortDescription
     * @param string|null                $longDescription
     * @param bool                       $isBuiltin
     * @param bool                       $isAbstract
     * @param bool                       $isFinal
     * @param bool                       $isAnnotation
     * @param bool                       $isDeprecated
     * @param bool                       $hasDocblock
     * @param Structure[]                $parents
     * @param Structure[]                $interfaces
     * @param Structure[]                $traits
     * @param StructureTraitAlias[]      $traitAliases
     * @param StructureTraitPrecedence[] $traitPrecedences
     */
    public function __construct(
        string $name,
        string $fqcn,
        File $file,
        int $startLine,
        int $endLine,
        StructureType $type,
        string $shortDescription = null,
        string $longDescription = null,
        bool $isBuiltin,
        bool $isAbstract,
        bool $isFinal,
        bool $isAnnotation,
        bool $isDeprecated,
        bool $hasDocblock,
        array $parents,
        array $interfaces,
        array $traits,
        array $traitAliases,
        array $traitPrecedences
    ) {
        $this->id = Uuid::uuid4();
        $this->name = $name;
        $this->fqcn = $fqcn;
        $this->file = $file;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->type = $type;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->isBuiltin = $isBuiltin;
        $this->isAbstract = $isAbstract;
        $this->isFinal = $isFinal;
        $this->isAnnotation = $isAnnotation;
        $this->isDeprecated = $isDeprecated;
        $this->hasDocblock = $hasDocblock;
        $this->parents = new ArrayCollection($parents);
        $this->interfaces = new ArrayCollection($interfaces);
        $this->traits = new ArrayCollection($traits);
        $this->traitAliases = new ArrayCollection($traitAliases);
        $this->traitPrecedences = new ArrayCollection($traitPrecedences);

        $this->constants = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->methods = new ArrayCollection();
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
     * @return string
     */
    public function getFqcn(): string
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
     * @return StructureType
     */
    public function getType(): StructureType
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * @return string|null
     */
    public function getLongDescription()
    {
        return $this->longDescription;
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
    public function getIsAbstract(): bool
    {
        return $this->isAbstract;
    }

    /**
     * @return bool
     */
    public function getIsFinal(): bool
    {
        return $this->isFinal;
    }

    /**
     * @return bool
     */
    public function getIsAnnotation(): bool
    {
        return $this->isAnnotation;
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
     * @return Structure[]
     */
    public function getParents(): array
    {
        return $this->parents->toArray();
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addParent(Structure $structure): void
    {
        $this->parents->add($structure);

        $structure->addChild($this);
    }

    /**
     * @return Structure[]
     */
    public function getChildren(): array
    {
        return $this->children->toArray();
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addChild(Structure $structure): void
    {
        $this->children->add($structure);

        $structure->addParent($this);
    }

    /**
     * @return Structure[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces->toArray();
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addInterface(Structure $structure): void
    {
        $this->interfaces->add($structure);

        $structure->addImplementor($this);
    }

    /**
     * @return Structure[]
     */
    public function getImplementors(): array
    {
        return $this->implementors->toArray();
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addImplementor(Structure $structure): void
    {
        $this->implementors->add($structure);

        $structure->addInterface($this);
    }

    /**
     * @return Structure[]
     */
    public function getTraits(): array
    {
        return $this->traits->toArray();
    }

    /**
     * @return Structure[]
     */
    public function getTraitUsers(): array
    {
        return $this->traitUsers->toArray();
    }

    /**
     * @return StructureTraitAlias[]
     */
    public function getTraitAliases(): array
    {
        return $this->traitAliases->toArray();
    }

    /**
     * @return StructureTraitPrecedence[]
     */
    public function getTraitPrecedences(): array
    {
        return $this->traitPrecedences->toArray();
    }

    /**
     * @return Constant[]
     */
    public function getConstants(): array
    {
        return $this->constants->toArray();
    }

    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties->toArray();
    }

    /**
     * @return Function_[]
     */
    public function getMethods(): array
    {
        return $this->methods->toArray();
    }
}
