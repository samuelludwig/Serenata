<?php

namespace Serenata\Indexing\Structures;

use Serenata\Common\Range;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents a (global) function.
 */
class Function_ extends FunctionLike
{
    /**
     * @var string
     */
    private $fqcn;

    /**
     * @param string              $name
     * @param string              $fqcn
     * @param File                $file
     * @param Range               $range
     * @param bool                $isDeprecated
     * @param string|null         $shortDescription
     * @param string|null         $longDescription
     * @param string|null         $returnDescription
     * @param string|null         $returnTypeHint
     * @param bool                $hasDocblock
     * @param array[]             $throws
     * @param TypeInfo[]          $returnTypes
     */
    public function __construct(
        string $name,
        string $fqcn,
        File $file,
        Range $range,
        bool $isDeprecated,
        ?string $shortDescription,
        ?string $longDescription,
        ?string $returnDescription,
        ?string $returnTypeHint,
        bool $hasDocblock,
        array $throws,
        array $returnTypes
    ) {
        $this->id = uniqid('', true);
        $this->name = $name;
        $this->fqcn = $fqcn;
        $this->file = $file;
        $this->range = $range;
        $this->isDeprecated = $isDeprecated;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->returnDescription = $returnDescription;
        $this->returnTypeHint = $returnTypeHint;
        $this->hasDocblock = $hasDocblock;
        $this->throws = $throws;
        $this->returnTypes = $returnTypes;

        $this->parameters = new ArrayCollection();

        $file->addFunction($this);
    }

    /**
     * @return string
     */
    public function getFqcn(): string
    {
        return $this->fqcn;
    }
}
