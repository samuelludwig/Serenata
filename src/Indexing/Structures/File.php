<?php

namespace Serenata\Indexing\Structures;

use DateTime;
use OutOfRangeException;

use Doctrine\Common\Collections\ArrayCollection;

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
    private $uri;

    /**
     * @var string|null
     */
    private $lastIndexedSourceHash;

    /**
     * @var DateTime
     */
    private $indexedOn;

    /**
     * @var ArrayCollection
     */
    private $constants;

    /**
     * @var ArrayCollection
     */
    private $functions;

    /**
     * @var ArrayCollection
     */
    private $classlikes;

    /**
     * @var ArrayCollection
     */
    private $namespaces;

    /**
     * @var ArrayCollection
     */
    private $metaStaticMethodTypes;

    /**
     * @param string          $uri
     * @param DateTime        $indexedOn
     * @param FileNamespace[] $namespaces
     */
    public function __construct(string $uri, DateTime $indexedOn, array $namespaces)
    {
        $this->id = uniqid('', true);
        $this->uri = $uri;
        $this->lastIndexedSourceHash = null;
        $this->indexedOn = $indexedOn;
        $this->namespaces = new ArrayCollection($namespaces);

        $this->constants = new ArrayCollection();
        $this->functions = new ArrayCollection();
        $this->classlikes = new ArrayCollection();
        $this->metaStaticMethodTypes = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getLastIndexedSourceHash(): ?string
    {
        return $this->lastIndexedSourceHash;
    }

    /**
     * @param string|null $lastIndexedSourceHash
     *
     * @return static
     */
    public function setLastIndexedSourceHash(?string $lastIndexedSourceHash)
    {
        $this->lastIndexedSourceHash = $lastIndexedSourceHash;
        return $this;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return DateTime
     */
    public function getIndexedOn(): DateTime
    {
        return $this->indexedOn;
    }

    /**
     * @param DateTime $indexedOn
     *
     * @return static
     */
    public function setIndexedOn(DateTime $indexedOn)
    {
        $this->indexedOn = $indexedOn;
        return $this;
    }

    /**
     * @return Constant[]
     */
    public function getConstants(): array
    {
        return array_values($this->constants->toArray());
    }

    /**
     * @param Constant $constant
     */
    public function addConstant(Constant $constant): void
    {
        $this->constants->add($constant);
    }

    /**
     * @param Constant $constant
     */
    public function removeConstant(Constant $constant): void
    {
        if (!$this->constants->contains($constant)) {
            throw new OutOfRangeException('Can not remove function from file that isn\'t even part of file');
        }

        $this->constants->removeElement($constant);
    }

    /**
     * @return Function_[]
     */
    public function getFunctions(): array
    {
        return array_values($this->functions->toArray());
    }

    /**
     * @param Function_ $function
     */
    public function addFunction(Function_ $function): void
    {
        $this->functions->add($function);
    }

    /**
     * @param Function_ $function
     */
    public function removeFunction(Function_ $function): void
    {
        if (!$this->functions->contains($function)) {
            throw new OutOfRangeException('Can not remove function from file that isn\'t even part of file');
        }

        $this->functions->removeElement($function);
    }

    /**
     * @return Classlike[]
     */
    public function getClasslikes(): array
    {
        return array_values($this->classlikes->toArray());
    }

    /**
     * @param Classlike $classlike
     */
    public function addClasslike(Classlike $classlike): void
    {
        $this->classlikes->add($classlike);
    }

    /**
     * @param Classlike $classlike
     */
    public function removeClasslike(Classlike $classlike): void
    {
        if (!$this->classlikes->contains($classlike)) {
            throw new OutOfRangeException('Can not remove classlike from file that isn\'t even part of file');
        }

        $this->classlikes->removeElement($classlike);
    }

    /**
     * @return FileNamespace[]
     */
    public function getNamespaces(): array
    {
        return array_values($this->namespaces->toArray());
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

    /**
     * @param FileNamespace $namespace
     */
    public function removeNamespace(FileNamespace $namespace): void
    {
        if (!$this->namespaces->contains($namespace)) {
            throw new OutOfRangeException('Can not remove namespace from file that isn\'t even part of file');
        }

        $this->namespaces->removeElement($namespace);
    }

    /**
     * @return MetaStaticMethodType[]
     */
    public function getMetaStaticMethodTypes(): array
    {
        return array_values($this->metaStaticMethodTypes->toArray());
    }

    /**
     * @param MetaStaticMethodType $metaStaticMethodType
     *
     * @return void
     */
    public function addMetaStaticMethodType(MetaStaticMethodType $metaStaticMethodType): void
    {
        $this->metaStaticMethodTypes->add($metaStaticMethodType);
    }

    /**
     * @param MetaStaticMethodType $metaStaticMethodType
     */
    public function removeMetaStaticMethodType(MetaStaticMethodType $metaStaticMethodType): void
    {
        if (!$this->metaStaticMethodTypes->contains($metaStaticMethodType)) {
            throw new OutOfRangeException(
                'Can not remove meta static method type from file that isn\'t even part of file'
            );
        }

        $this->metaStaticMethodTypes->removeElement($metaStaticMethodType);
    }
}
