<?php

namespace Serenata\Indexing\Structures;

/**
 * Represents a class constant.
 */
class ClassConstant extends ConstantLike
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
     * @param string              $name
     * @param File                $file
     * @param int                 $startLine
     * @param int                 $endLine
     * @param string              $defaultValue
     * @param bool                $isDeprecated
     * @param bool                $hasDocblock
     * @param string|null         $shortDescription
     * @param string|null         $longDescription
     * @param string|null         $typeDescription
     * @param TypeInfo[]          $types
     * @param Classlike           $classlike
     * @param AccessModifier      $accessModifier
     */
    public function __construct(
        string $name,
        File $file,
        int $startLine,
        int $endLine,
        string $defaultValue,
        bool $isDeprecated,
        bool $hasDocblock,
        ?string $shortDescription,
        ?string $longDescription,
        ?string $typeDescription,
        array $types,
        Classlike $classlike,
        AccessModifier $accessModifier
    ) {
        $this->id = uniqid('', true);
        $this->name = $name;
        $this->file = $file;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->defaultValue = $defaultValue;
        $this->isDeprecated = $isDeprecated;
        $this->hasDocblock = $hasDocblock;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->typeDescription = $typeDescription;
        $this->types = $types;
        $this->classlike = $classlike;
        $this->accessModifier = $accessModifier;

        $classlike->addConstant($this);
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
}
