<?php

namespace Serenata\Indexing\Structures;

/**
 * Represents trait method precedence in a trait.
 */
class TraitTraitPrecedence extends ClasslikeTraitPrecedence
{
    /**
     * @var Trait_
     */
    private $trait;

    /**
     * @param Trait_ $trait
     * @param string $traitFqcn
     * @param string $name
     */
    public function __construct(Trait_ $trait, string $traitFqcn, string $name)
    {
        $this->id = uniqid('', true);
        $this->trait = $trait;
        $this->traitFqcn = $traitFqcn;
        $this->name = $name;

        $trait->addTraitPrecedence($this);
    }

    /**
     * @return Trait_
     */
    public function getTrait(): Trait_
    {
        return $this->trait;
    }
}
