<?php

namespace Serenata\Symbols;

use JsonSerializable;

use Serenata\Utility\Location;

/**
 * Represents document symbol information.
 *
 * This is a value object and immutable.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#textDocument_documentSymbol
 */
final class SymbolInformation implements JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $kind;

    /**
     * @var bool|null
     */
    private $deprecated;

    /**
     * @var Location
     */
    private $location;

    /**
     * @var string|null
     */
    private $containerName;

    /**
     * @param string      $name
     * @param int         $kind
     * @param bool|null   $deprecated
     * @param Location    $location
     * @param string|null $containerName
     */
    public function __construct(string $name, int $kind, ?bool $deprecated, Location $location, ?string $containerName)
    {
        $this->name = $name;
        $this->kind = $kind;
        $this->deprecated = $deprecated;
        $this->location = $location;
        $this->containerName = $containerName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getKind(): int
    {
        return $this->kind;
    }

    /**
     * @return bool|null
     */
    public function getDeprecated(): ?bool
    {
        return $this->deprecated;
    }

    /**
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * @return string|null
     */
    public function getContainerName(): ?string
    {
        return $this->containerName;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'name'          => $this->getName(),
            'kind'          => $this->getKind(),
            'deprecated'    => $this->getDeprecated(),
            'location'      => $this->getLocation(),
            'containerName' => $this->getContainerName(),
        ];
    }
}
