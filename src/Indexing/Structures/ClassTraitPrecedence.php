<?php

namespace PhpIntegrator\Indexing\Structures;

use Ramsey\Uuid\Uuid;

/**
 * Represents trait method precedence in a class.
 */
class ClassTraitPrecedence
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Class_
     */
    private $class;

    /**
     * @var string
     */
    private $traitFqcn;

    /**
     * @var string
     */
    private $name;

    /**
     * @param Class_ $class
     * @param string $traitFqcn
     * @param string $name
     */
    public function __construct(Class_ $class, string $traitFqcn, string $name)
    {
        $this->id = (string) Uuid::uuid4();
        $this->class = $class;
        $this->traitFqcn = $traitFqcn;
        $this->name = $name;

        $class->addTraitPrecedence($this);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Class_
     */
    public function getClass(): Class_
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getTraitFqcn(): string
    {
        return $this->traitFqcn;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
