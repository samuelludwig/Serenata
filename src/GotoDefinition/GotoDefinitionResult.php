<?php

namespace Serenata\GotoDefinition;

use JsonSerializable;

/**
 * The result of a goto definition request.
 */
final class GotoDefinitionResult implements JsonSerializable
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var int
     */
    private $line;

    /**
     * @param string $uri
     * @param int    $line
     */
    public function __construct(string $uri, int $line)
    {
        $this->uri = $uri;
        $this->line = $line;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'uri'  => $this->getUri(),
            'line' => $this->getLine(),
        ];
    }
}
