<?php

namespace Serenata\Utility;

use JsonSerializable;

use Serenata\Common\Range;

/**
 * Represents a location inside a resource.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#location
 */
final class Location implements JsonSerializable
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var Range
     */
    private $range;

    /**
     * @param string $uri
     * @param Range  $range
     */
    public function __construct(string $uri, Range $range)
    {
        $this->uri = $uri;
        $this->range = $range;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return Range
     */
    public function getRange(): Range
    {
        return $this->range;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'uri'   => $this->getUri(),
            'range' => $this->getRange(),
        ];
    }
}
