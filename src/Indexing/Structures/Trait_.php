<?php

namespace Serenata\Indexing\Structures;

use Serenata\Common\Range;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents a trait.
 *
 * phpcs:disable
 *
 * @final
 */
class Trait_ extends Classlike
{
    // phpcs:enable
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
     * @var bool
     */
    private $isAddingTrait = false;

    /**
     * @var bool
     */
    private $isAddingTraitUser = false;

    /**
     * @param string      $name
     * @param string      $fqcn
     * @param File        $file
     * @param Range       $range
     * @param string|null $shortDescription
     * @param string|null $longDescription
     * @param bool        $isDeprecated
     * @param bool        $hasDocblock
     */
    public function __construct(
        string $name,
        string $fqcn,
        File $file,
        Range $range,
        ?string $shortDescription,
        ?string $longDescription,
        bool $isDeprecated,
        bool $hasDocblock
    ) {
        $this->id = uniqid('', true);
        $this->name = $name;
        $this->fqcn = $fqcn;
        $this->file = $file;
        $this->range = $range;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->isDeprecated = $isDeprecated;
        $this->hasDocblock = $hasDocblock;

        $this->traitFqcns = [];
        $this->traitUserFqcns = [];

        $this->traitAliases = new ArrayCollection();
        $this->traitPrecedences = new ArrayCollection();

        $this->constants = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->methods = new ArrayCollection();

        $file->addClasslike($this);
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
     * @return string[]
     */
    public function getTraitUserFqcns(): array
    {
        return $this->traitUserFqcns;
    }

    /**
     * @param Class_|Trait_ $classlike
     *
     * @return void
     */
    public function addTraitUser(Classlike $classlike): void
    {
        if ($this->isAddingTraitUser) {
            return; // Don't loop infinitely whilst maintaining bidirectional association.
        }

        $this->isAddingTraitUser = true;

        $this->traitUserFqcns[] = $classlike->getFqcn();

        $classlike->addTrait($this);

        $this->isAddingTraitUser = false;
    }

    /**
     * @return TraitTraitAlias[]
     */
    public function getTraitAliases(): array
    {
        return $this->traitAliases->toArray();
    }

    /**
     * @param TraitTraitAlias $classlikeTraitAlias
     *
     * @return void
     */
    public function addTraitAlias(TraitTraitAlias $classlikeTraitAlias): void
    {
        $this->traitAliases->add($classlikeTraitAlias);
    }

    /**
     * @return TraitTraitPrecedence[]
     */
    public function getTraitPrecedences(): array
    {
        return $this->traitPrecedences->toArray();
    }

    /**
     * @param TraitTraitPrecedence $classlikeTraitPrecedence
     *
     * @return void
     */
    public function addTraitPrecedence(TraitTraitPrecedence $classlikeTraitPrecedence): void
    {
        $this->traitPrecedences->add($classlikeTraitPrecedence);
    }

    /**
     * @inheritDoc
     */
    public function getTypeName(): string
    {
        return ClasslikeTypeNameValue::TRAIT_;
    }
}
