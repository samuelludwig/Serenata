<?php

namespace PhpIntegrator\Indexing\Structures;

use Ramsey\Uuid\Uuid;

/**
 * Represents trait method precedence in a structure.
 */
class StructureTraitPrecedence
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var string
     */
    private $traitFqcn;

    /**
     * @var string
     */
    private $name;

    /**
     * @param Structure $structure
     * @param string    $traitFqcn
     * @param string    $name
     */
    public function __construct(Structure $structure, string $traitFqcn, string $name)
    {
        $this->id = (string) Uuid::uuid4();
        $this->structure = $structure;
        $this->traitFqcn = $traitFqcn;
        $this->name = $name;

        $structure->addTraitPrecedence($this);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Structure
     */
    public function getStructure(): Structure
    {
        return $this->structure;
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
