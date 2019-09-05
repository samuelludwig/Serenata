<?php

namespace Serenata\Indexing\Structures;

/**
 * Represents an aliased trait method in a trait.
 *
 * @final
 */
class TraitTraitAlias extends ClasslikeTraitAlias
{
    /**
     * @var Trait_
     */
    private $trait;

    /**
     * @param Trait_              $trait
     * @param string|null         $traitFqcn
     * @param AccessModifier|null $accessModifier
     * @param string              $name
     * @param string|null         $alias
     */
    public function __construct(
        Trait_ $trait,
        ?string $traitFqcn,
        ?AccessModifier $accessModifier,
        string $name,
        ?string $alias
    ) {
        $this->id = uniqid('', true);
        $this->trait = $trait;
        $this->traitFqcn = $traitFqcn;
        $this->accessModifier = $accessModifier;
        $this->name = $name;
        $this->alias = $alias;

        $trait->addTraitAlias($this);
    }

    /**
     * @return Trait_
     */
    public function getTrait(): Trait_
    {
        return $this->trait;
    }
}
