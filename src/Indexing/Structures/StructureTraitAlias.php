<?php

namespace PhpIntegrator\Indexing\Structures;

use Ramsey\Uuid\Uuid;

/**
 * Represents an aliased trait method in a structure.
 */
class StructureTraitAlias
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
     * @var string|null
     */
    private $traitFqcn;

    /**
     * @var AccessModifier|null
     */
    private $accessModifier;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $alias;

    /**
     * @param Structure           $structure
     * @param string|null         $traitFqcn
     * @param AccessModifier|null $accessModifier
     * @param string              $name
     * @param string|null         $alias
     */
    public function __construct(
        Structure $structure,
        ?string $traitFqcn,
        ?AccessModifier $accessModifier,
        string $name,
        ?string $alias
    ) {
        $this->id = (string) Uuid::uuid4();
        $this->structure = $structure;
        $this->traitFqcn = $traitFqcn;
        $this->accessModifier = $accessModifier;
        $this->name = $name;
        $this->alias = $alias;

        $structure->addTraitAlias($this);
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
     * @return string|null
     */
    public function getTraitFqcn(): ?string
    {
        return $this->traitFqcn;
    }

    /**
     * @return AccessModifier|null
     */
    public function getAccessModifier(): ?AccessModifier
    {
        return $this->accessModifier;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }
}
