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
     * @var Structure
     */
    private $trait;

    /**
     * @var string
     */
    private $name;

    /**
     * @param Structure $structure
     * @param Structure $trait
     * @param string    $name
     */
    public function __construct(Structure $structure, Structure $trait, string $name)
    {
        $this->id = Uuid::uuid4();
        $this->structure = $structure;
        $this->trait = $trait;
        $this->name = $name;
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
     * @return Structure
     */
    public function getTrait(): Structure
    {
        return $this->trait;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
