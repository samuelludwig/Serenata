<?php

namespace Serenata\Indexing\Structures;

use Serenata\Common\Range;

/**
 * Represents an import in a namespace inside a file.
 */
class FileNamespaceImport
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
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $kind;

    /**
     * @var FileNamespace
     */
    private $namespace;

    /**
     * @param Range         $range
     * @param string        $alias
     * @param string        $name
     * @param string        $kind
     * @param FileNamespace $namespace
     */
    public function __construct(Range $range, string $alias, string $name, string $kind, FileNamespace $namespace)
    {
        $this->id = uniqid('', true);
        $this->range = $range;
        $this->alias = $alias;
        $this->name = $name;
        $this->kind = $kind;
        $this->namespace = $namespace;

        $namespace->addImport($this);
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
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @return FileNamespace
     */
    public function getNamespace(): FileNamespace
    {
        return $this->namespace;
    }
}
