<?php

namespace PhpIntegrator\Indexing\Structures;

use Ramsey\Uuid\Uuid;

/**
 * Represents a function.
 */
class Function_
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $fqcn;

    /**
     * @var File
     */
    private $file;

    /**
     * @var int
     */
    private $startLine;

    /**
     * @var int
     */
    private $endLine;

    /**
     * @var bool
     */
    private $isBuiltin;

    /**
     * @var bool
     */
    private $isDeprecated;

    /**
     * @var string|null
     */
    private $shortDescription;

    /**
     * @var string|null
     */
    private $longDescription;

    /**
     * @var string|null
     */
    private $returnDescription;

    /**
     * @var string|null
     */
    private $returnTypeHint;

    /**
     * @var Structure|null
     */
    private $structure;

    /**
     * @var AccessModifier|null
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
     * @var bool
     */
    private $hasDocblock;

    /**
     * @var array[]
     */
    private $throws;

    /**
     * @var FunctionParameter[]
     */
    private $parameters;

    /**
     * @var TypeInfo[]
     */
    private $returnTypes;

    /**
     * @param string              $name
     * @param string|null         $fqcn
     * @param File                $file
     * @param int                 $startLine
     * @param int                 $endLine
     * @param bool                $isBuiltin
     * @param bool                $isDeprecated
     * @param string|null         $shortDescription
     * @param string|null         $longDescription
     * @param string|null         $returnDescription
     * @param string|null         $returnTypeHint
     * @param Structure|null      $structure
     * @param AccessModifier|null $accessModifier
     * @param bool                $isMagic
     * @param bool                $isStatic
     * @param bool                $isAbstract
     * @param bool                $isFinal
     * @param bool                $hasDocblock
     * @param array[]             $throws
     * @param FunctionParameter[] $parameters
     * @param TypeInfo[]          $returnTypes
     */
    public function __construct(
        string $name,
        ?string $fqcn,
        File $file,
        int $startLine,
        int $endLine,
        bool $isBuiltin,
        bool $isDeprecated,
        ?string $shortDescription,
        ?string $longDescription,
        ?string $returnDescription,
        ?string $returnTypeHint,
        ?Structure $structure,
        ?AccessModifier $accessModifier,
        bool $isMagic,
        bool $isStatic,
        bool $isAbstract,
        bool $isFinal,
        bool $hasDocblock,
        array $throws,
        array $parameters,
        array $returnTypes
    ) {
        $this->id = (string) Uuid::uuid4();
        $this->name = $name;
        $this->fqcn = $fqcn;
        $this->file = $file;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->isBuiltin = $isBuiltin;
        $this->isDeprecated = $isDeprecated;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->returnDescription = $returnDescription;
        $this->returnTypeHint = $returnTypeHint;
        $this->structure = $structure;
        $this->accessModifier = $accessModifier;
        $this->isMagic = $isMagic;
        $this->isStatic = $isStatic;
        $this->isAbstract = $isAbstract;
        $this->isFinal = $isFinal;
        $this->hasDocblock = $hasDocblock;
        $this->throws = $throws;
        $this->parameters = $parameters;
        $this->returnTypes = $returnTypes;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
    public function getFqcn(): ?string
    {
        return $this->fqcn;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getStartLine(): int
    {
        return $this->startLine;
    }

    /**
     * @return int
     */
    public function getEndLine(): int
    {
        return $this->endLine;
    }

    /**
     * @return bool
     */
    public function getIsBuiltin(): bool
    {
        return $this->isBuiltin;
    }

    /**
     * @return bool
     */
    public function getIsDeprecated(): bool
    {
        return $this->isDeprecated;
    }

    /**
     * @return string|null
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * @return string|null
     */
    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    /**
     * @return string|null
     */
    public function getReturnDescription(): ?string
    {
        return $this->returnDescription;
    }

    /**
     * @return string|null
     */
    public function getReturnTypeHint(): ?string
    {
        return $this->returnTypeHint;
    }

    /**
     * @return Structure|null
     */
    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    /**
     * @return AccessModifier|null
     */
    public function getAccessModifier(): ?AccessModifier
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

    /**
     * @return bool
     */
    public function getHasDocblock(): bool
    {
        return $this->hasDocblock;
    }

    /**
     * @return array[]
     */
    public function getThrows(): array
    {
        return $this->throws;
    }

    /**
     * @return FunctionParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param FunctionParameter $parameter
     *
     * @return void
     */
    public function addParameter(FunctionParameter $parameter): void
    {
        $this->parameters->add($parameter);
    }

    /**
     * @return TypeInfo[]
     */
    public function getReturnTypes(): array
    {
        return $this->returnTypes;
    }
}
