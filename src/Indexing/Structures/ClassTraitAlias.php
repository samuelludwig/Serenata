<?php

namespace Serenata\Indexing\Structures;

/**
 * Represents an aliased trait method in a class.
 */
class ClassTraitAlias extends ClasslikeTraitAlias
{
    /**
     * @var Class_
     */
    private $class;

    /**
     * @param Class_           $class
     * @param string|null         $traitFqcn
     * @param AccessModifier|null $accessModifier
     * @param string              $name
     * @param string|null         $alias
     */
    public function __construct(
        Class_ $class,
        ?string $traitFqcn,
        ?AccessModifier $accessModifier,
        string $name,
        ?string $alias
    ) {
        $this->id = uniqid('', true);
        $this->class = $class;
        $this->traitFqcn = $traitFqcn;
        $this->accessModifier = $accessModifier;
        $this->name = $name;
        $this->alias = $alias;

        $class->addTraitAlias($this);
    }

    /**
     * @return Class_
     */
    public function getClass(): Class_
    {
        return $this->class;
    }
}
