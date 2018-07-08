<?php

namespace Serenata\Indexing\Structures;

use AssertionError;

use Serenata\Common\Range;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents a class.
 */
class Class_ extends Classlike
{
    /**
     * @var bool
     */
    private $isAnonymous;

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
     * @var string|null
     */
    private $parentFqcn;

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
    private $traitFqcns;

    /**
     * @var ArrayCollection
     */
    private $traitAliases;

    /**
     * @var ArrayCollection
     */
    private $traitPrecedences;

    /**
     * @var bool
     */
    private $isAddingTrait = false;

    /**
     * @var bool
     */
    private $isAddingInterface = false;

    /**
     * @param string      $name
     * @param string      $fqcn
     * @param File        $file
     * @param Range       $range
     * @param string|null $shortDescription
     * @param string|null $longDescription
     * @param bool        $isAnonymous
     * @param bool        $isAbstract
     * @param bool        $isFinal
     * @param bool        $isAnnotation
     * @param bool        $isDeprecated
     * @param bool        $hasDocblock
     * @param Class_|null $parent
     */
    public function __construct(
        string $name,
        string $fqcn,
        File $file,
        Range $range,
        string $shortDescription = null,
        string $longDescription = null,
        bool $isAnonymous,
        bool $isAbstract,
        bool $isFinal,
        bool $isAnnotation,
        bool $isDeprecated,
        bool $hasDocblock,
        ?Class_ $parent
    ) {
        $this->id = uniqid('', true);
        $this->name = $name;
        $this->fqcn = $fqcn;
        $this->file = $file;
        $this->range = $range;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->isAnonymous = $isAnonymous;
        $this->isAbstract = $isAbstract;
        $this->isFinal = $isFinal;
        $this->isAnnotation = $isAnnotation;
        $this->isDeprecated = $isDeprecated;
        $this->hasDocblock = $hasDocblock;

        $this->childFqcns = [];
        $this->interfaceFqcns = [];
        $this->traitFqcns = [];

        $this->traitAliases = new ArrayCollection();
        $this->traitPrecedences = new ArrayCollection();

        $this->constants = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->methods = new ArrayCollection();

        $this->setParent($parent);

        $file->addClasslike($this);
    }

    /**
     * @return bool
     */
    public function getIsAnonymous(): bool
    {
        return $this->isAnonymous;
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
     * @return string|null
     */
    public function getParentFqcn(): ?string
    {
        return $this->parentFqcn;
    }

    /**
     * @param string|null $parentFqcn
     *
     * @return void
     */
    public function setParentFqcn(?string $parentFqcn): void
    {
        $this->parentFqcn = $parentFqcn;
    }

    /**
     * @param Class_|null $parent
     *
     * @return void
     */
    public function setParent(?Class_ $parent): void
    {
        if ($this->parentFqcn !== null) {
            throw new AssertionError('Moving an item to another parent is not supported');
        }

        if ($parent === null) {
            $this->parentFqcn = null;
        } else {
            $this->parentFqcn = $parent->getFqcn();

            $parent->addChild($this);
        }
    }

    /**
     * @return string[]
     */
    public function getChildFqcns(): array
    {
        return $this->childFqcns;
    }

    /**
     * @param Class_ $class
     *
     * @return void
     */
    public function addChild(Class_ $class): void
    {
        $this->childFqcns[] = $class->getFqcn();
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
     * @param Interface_ $interface
     *
     * @return void
     */
    public function addInterface(Interface_ $interface): void
    {
        if ($this->isAddingInterface) {
            return; // Don't loop infinitely whilst maintaining bidirectional association.
        }

        $this->isAddingInterface = true;

        $this->addInterfaceFqcn($interface->getFqcn());

        $interface->addImplementor($this);

        $this->isAddingInterface = false;
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
     * @param Trait_ $trait
     *
     * @return void
     */
    public function addTrait(Trait_ $trait): void
    {
        if ($this->isAddingTrait) {
            return; // Don't loop infinitely whilst maintaining bidirectional association.
        }

        $this->isAddingTrait = true;

        $this->addTraitFqcn($trait->getFqcn());

        $trait->addTraitUser($this);

        $this->isAddingTrait = false;
    }

    /**
     * @return ClassTraitAlias[]
     */
    public function getTraitAliases(): array
    {
        return $this->traitAliases->toArray();
    }

    /**
     * @param ClassTraitAlias $classlikeTraitAlias
     *
     * @return void
     */
    public function addTraitAlias(ClassTraitAlias $classlikeTraitAlias): void
    {
        $this->traitAliases->add($classlikeTraitAlias);
    }

    /**
     * @return ClassTraitPrecedence[]
     */
    public function getTraitPrecedences(): array
    {
        return $this->traitPrecedences->toArray();
    }

    /**
     * @param ClassTraitPrecedence $classlikeTraitPrecedence
     *
     * @return void
     */
    public function addTraitPrecedence(ClassTraitPrecedence $classlikeTraitPrecedence): void
    {
        $this->traitPrecedences->add($classlikeTraitPrecedence);
    }

    /**
     * @inheritDoc
     */
    public function getTypeName(): string
    {
        return ClasslikeTypeNameValue::CLASS_;
    }
}
