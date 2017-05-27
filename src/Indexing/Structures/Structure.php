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
     * @var string[]
     */
    private $parentFqcns;

    /**
     * @var string[]
     */
    private $childFqcns;

    /**
     * @var string[]
     */
    private $interfaceFqcns;

    /**
     * @var string[]
     */
    private $implementorFqcns;

    /**
     * @var string[]
     */
    private $traitFqcns;

    /**
     * @var string[]
     */
    private $traitUserFqcns;

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
     * @param string        $name
     * @param string        $fqcn
     * @param File          $file
     * @param int           $startLine
     * @param int           $endLine
     * @param StructureType $type
     * @param string|null   $shortDescription
     * @param string|null   $longDescription
     * @param bool          $isAbstract
     * @param bool          $isFinal
     * @param bool          $isAnnotation
     * @param bool          $isDeprecated
     * @param bool          $hasDocblock
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
        bool $isAbstract,
        bool $isFinal,
        bool $isAnnotation,
        bool $isDeprecated,
        bool $hasDocblock
    ) {
        $this->id = (string) Uuid::uuid4();
        $this->name = $name;
        $this->fqcn = $fqcn;
        $this->file = $file;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->type = $type;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->isAbstract = $isAbstract;
        $this->isFinal = $isFinal;
        $this->isAnnotation = $isAnnotation;
        $this->isDeprecated = $isDeprecated;
        $this->hasDocblock = $hasDocblock;

        $this->parentFqcns = [];
        $this->childFqcns = [];
        $this->interfaceFqcns = [];
        $this->implementorFqcns = [];
        $this->traitFqcns = [];
        $this->traitUserFqcns = [];

        $this->traitAliases = new ArrayCollection();
        $this->traitPrecedences = new ArrayCollection();

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
     * @return string[]
     */
    public function getParentFqcns(): array
    {
        return $this->parentFqcns;
    }

    /**
     * @param string $fqcn
     *
     * @return void
     */
    public function addParentFqcn(string $fqcn): void
    {
        $this->parentFqcns[] = $fqcn;
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addParent(Structure $structure): void
    {
        $this->addParentFqcn($structure->getFqcn());

        $structure->childFqcns[] = $this->getFqcn();
    }

    /**
     * @return string[]
     */
    public function getChildFqcns(): array
    {
        return $this->childFqcns;
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addChild(Structure $structure): void
    {
        $structure->addParent($this);
    }

    /**
     * @return string[]
     */
    public function getInterfaceFqcns(): array
    {
        return $this->interfaceFqcns;
    }

    /**
     * @param string $fqcn
     *
     * @return void
     */
    public function addInterfaceFqcn(string $fqcn): void
    {
        $this->interfaceFqcns[] = $fqcn;
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addInterface(Structure $structure): void
    {
        $this->addInterfaceFqcn($structure->getFqcn());

        $structure->implementorFqcns[] = $this->getFqcn();
    }

    /**
     * @return string[]
     */
    public function getImplementorFqcns(): array
    {
        return $this->implementorFqcns;
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addImplementor(Structure $structure): void
    {
        $structure->addInterface($this);
    }

    /**
     * @return string[]
     */
    public function getTraitFqcns(): array
    {
        return $this->traitFqcns;
    }

    /**
     * @param string $fqcn
     *
     * @return void
     */
    public function addTraitFqcn(string $fqcn): void
    {
        $this->traitFqcns[] = $fqcn;
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addTrait(Structure $structure): void
    {
        $this->addTraitFqcn($structure->getFqcn());

        $structure->traitUserFqcns[] = $this->getFqcn();
    }

    /**
     * @return string[]
     */
    public function getTraitUserFqcns(): array
    {
        return $this->traitUserFqcns;
    }

    /**
     * @param Structure $structure
     *
     * @return void
     */
    public function addTraitUser(Structure $structure): void
    {
        $structure->addTrait($this);
    }

    /**
     * @return StructureTraitAlias[]
     */
    public function getTraitAliases(): array
    {
        return $this->traitAliases->toArray();
    }

    /**
     * @param StructureTraitAlias $structureTraitAlias
     *
     * @return void
     */
    public function addTraitAlias(StructureTraitAlias $structureTraitAlias): void
    {
        $this->traitAliases->add($structureTraitAlias);
    }

    /**
     * @return StructureTraitPrecedence[]
     */
    public function getTraitPrecedences(): array
    {
        return $this->traitPrecedences->toArray();
    }

    /**
     * @param StructureTraitPrecedence $structureTraitPrecedence
     *
     * @return void
     */
    public function addTraitPrecedence(StructureTraitPrecedence $structureTraitPrecedence): void
    {
        $this->traitPrecedences->add($structureTraitPrecedence);
    }

    /**
     * @return Constant[]
     */
    public function getConstants(): array
    {
        return $this->constants->toArray();
    }

    /**
     * @param Constant $constant
     *
     * @return void
     */
    public function addConstant(Constant $constant): void
    {
        $this->constants->add($constant);
    }

    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties->toArray();
    }

    /**
     * @param Property $property
     *
     * @return void
     */
    public function addProperty(Property $property): void
    {
        $this->properties->add($property);
    }

    /**
     * @return Function_[]
     */
    public function getMethods(): array
    {
        return $this->methods->toArray();
    }

    /**
     * @param Function_ $method
     *
     * @return void
     */
    public function addMethod(Function_ $method): void
    {
        $this->methods->add($method);
    }
}
