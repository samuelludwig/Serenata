<?php

namespace Serenata\Indexing\Structures;

/**
 * Represents trait method precedence in a class.
 *
 * @final
 */
class ClassTraitPrecedence extends ClasslikeTraitPrecedence
{
    /**
     * @var Class_
     */
    private $class;

    /**
     * @param Class_ $class
     * @param string $traitFqcn
     * @param string $name
     */
    public function __construct(Class_ $class, string $traitFqcn, string $name)
    {
        $this->id = uniqid('', true);
        $this->class = $class;
        $this->traitFqcn = $traitFqcn;
        $this->name = $name;

        $class->addTraitPrecedence($this);
    }

    /**
     * @return Class_
     */
    public function getClass(): Class_
    {
        return $this->class;
    }
}
