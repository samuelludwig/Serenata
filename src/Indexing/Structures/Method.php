<?php

namespace PhpIntegrator\Indexing\Structures;

use Doctrine\Common\Collections\ArrayCollection;

use Ramsey\Uuid\Uuid;

/**
 * Represents a (class) method.
 */
final class Method extends FunctionLike
{
    /**
     * @var Classlike
     */
    private $classlike;

    /**
     * @var AccessModifier
     */
    private $accessModifier;

    /**
     * @var bool
     */
    private $isMagic;

    /**
     * @var bool
     */
    private $isStatic;

    /**
     * @var bool
     */
    private $isAbstract;

    /**
     * @var bool
     */
    private $isFinal;

    /**
     * @param string         $name
     * @param File           $file
     * @param int            $startLine
     * @param int            $endLine
     * @param bool           $isDeprecated
     * @param string|null    $shortDescription
     * @param string|null    $longDescription
     * @param string|null    $returnDescription
     * @param string|null    $returnTypeHint
     * @param Classlike      $classlike
     * @param AccessModifier $accessModifier
     * @param bool           $isMagic
     * @param bool           $isStatic
     * @param bool           $isAbstract
     * @param bool           $isFinal
     * @param bool           $hasDocblock
     * @param array[]        $throws
     * @param TypeInfo[]     $returnTypes
     */
    public function __construct(
        string $name,
        File $file,
        int $startLine,
        int $endLine,
        bool $isDeprecated,
        ?string $shortDescription,
        ?string $longDescription,
        ?string $returnDescription,
        ?string $returnTypeHint,
        Classlike $classlike,
        AccessModifier $accessModifier,
        bool $isMagic,
        bool $isStatic,
        bool $isAbstract,
        bool $isFinal,
        bool $hasDocblock,
        array $throws,
        array $returnTypes
    ) {
        $this->id = (string) Uuid::uuid4();
        $this->name = $name;
        $this->file = $file;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->isDeprecated = $isDeprecated;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->returnDescription = $returnDescription;
        $this->returnTypeHint = $returnTypeHint;
        $this->classlike = $classlike;
        $this->accessModifier = $accessModifier;
        $this->isMagic = $isMagic;
        $this->isStatic = $isStatic;
        $this->isAbstract = $isAbstract;
        $this->isFinal = $isFinal;
        $this->hasDocblock = $hasDocblock;
        $this->throws = $throws;
        $this->returnTypes = $returnTypes;

        $this->parameters = new ArrayCollection();

        $classlike->addMethod($this);
    }

    /**
     * @return Classlike
     */
    public function getClasslike(): Classlike
    {
        return $this->classlike;
    }

    /**
     * @return AccessModifier
     */
    public function getAccessModifier(): AccessModifier
    {
        return $this->accessModifier;
    }

    /**
     * @return bool
     */
    public function getIsMagic(): bool
    {
        return $this->isMagic;
    }

    /**
     * @return bool
     */
    public function getIsStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * @return bool
     */
    public function getIsAbstract(): bool
    {
        return $this->isAbstract;
    }

    /**
     * @return bool
     */
    public function getIsFinal(): bool
    {
        return $this->isFinal;
    }
}
