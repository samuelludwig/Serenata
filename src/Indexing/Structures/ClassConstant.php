<?php

namespace Serenata\Indexing\Structures;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use Serenata\Common\Range;

/**
 * Represents a class constant.
 *
 * @final
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
     * @param string         $name
     * @param File           $file
     * @param Range          $range
     * @param string         $defaultValue
     * @param bool           $isDeprecated
     * @param bool           $hasDocblock
     * @param string|null    $shortDescription
     * @param string|null    $longDescription
     * @param string|null    $typeDescription
     * @param TypeNode       $type
     * @param Classlike      $classlike
     * @param AccessModifier $accessModifier
     */
    public function __construct(
        string $name,
        File $file,
        Range $range,
        string $defaultValue,
        bool $isDeprecated,
        bool $hasDocblock,
        ?string $shortDescription,
        ?string $longDescription,
        ?string $typeDescription,
        TypeNode $type,
        Classlike $classlike,
        AccessModifier $accessModifier
    ) {
        $this->id = uniqid('', true);
        $this->name = $name;
        $this->file = $file;
        $this->range = $range;
        $this->defaultValue = $defaultValue;
        $this->isDeprecated = $isDeprecated;
        $this->hasDocblock = $hasDocblock;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->typeDescription = $typeDescription;
        $this->type = $type;
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
