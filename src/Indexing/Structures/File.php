<?php

namespace PhpIntegrator\Indexing\Structures;

use DateTime;

use Doctrine\Common\Collections\ArrayCollection;

use Ramsey\Uuid\Uuid;

/**
 * Represents a file.
 */
class File
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $path;

    /**
     * @var DateTime
     */
    private $indexedOn;

    /**
     * @var ArrayCollection
     */
    private $namespaces;

    /**
     * @param string          $path
     * @param DateTime        $indexedOn
     * @param FileNamespace[] $namespaces
     */
    public function __construct(string $path, DateTime $indexedOn, array $namespaces)
    {
        $this->id = Uuid::uuid4();
        $this->path = $path;
        $this->indexedOn = $indexedOn;
        $this->namespaces = new ArrayCollection($namespaces);
    }

    /**
     * @return int
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return DateTime
     */
    public function getIndexedOn(): DateTime
    {
        return $this->indexedOn;
    }

    /**
     * @return FileNamespace[]
     */
    public function getNamespaces(): array
    {
        return $this->namespaces->toArray();
    }

    /**
     * @param FileNamespace $namespace
     *
     * @return void
     */
    public function addNamespace(FileNamespace $namespace): void
    {
        $this->namespaces->add($namespace);
    }
}
