<?php

namespace Serenata\Indexing\Structures;

use Serenata\Common\Range;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents a namespace in a file.
 *
 * @final
 */
class FileNamespace
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Range
     */
    private $range;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var File
     */
    private $file;

    /**
     * @var ArrayCollection
     */
    private $imports;

    /**
     * @param Range                 $range
     * @param string|null           $name
     * @param File                  $file
     * @param FileNamespaceImport[] $imports
     */
    public function __construct(
        Range $range,
        ?string $name,
        File $file,
        array $imports
    ) {
        $this->id = uniqid('', true);
        $this->range = $range;
        $this->name = $name;
        $this->file = $file;
        $this->imports = new ArrayCollection($imports);

        $file->addNamespace($this);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Range
     */
    public function getRange(): Range
    {
        return $this->range;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return FileNamespaceImport[]
     */
    public function getImports(): array
    {
        return $this->imports->toArray();
    }

    /**
     * @param FileNamespaceImport $import
     *
     * @return void
     */
    public function addImport(FileNamespaceImport $import): void
    {
        $this->imports->add($import);
    }
}
