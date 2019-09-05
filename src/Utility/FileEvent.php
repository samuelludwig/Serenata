<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents file events used by the the workspace/didChangeWatchedFiles request.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#workspace_didChangeWatchedFiles
 */
final class FileEvent implements JsonSerializable
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var int
     */
    private $type;

    /**
     * @param string $uri
     * @param int    $type
     */
    public function __construct(string $uri, int $type)
    {
        $this->uri = $uri;
        $this->type = $type;
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
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->getUri(),
            'type' => $this->getType(),
        ];
    }
}
