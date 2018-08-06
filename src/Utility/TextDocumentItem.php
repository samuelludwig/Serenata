<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents a specific version of a text document.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#textdocumentitem
 */
final class TextDocumentItem implements JsonSerializable
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $text;

    /**
     * @var int
     */
    private $version;

    /**
     * @var string
     */
    private $languageId;

    /**
     * @param string $uri
     * @param string $text
     * @param int    $version
     * @param string $languageId
     */
    public function __construct(string $uri, string $text, int $version = 1, string $languageId = 'php')
    {
        $this->uri = $uri;
        $this->text = $text;
        $this->version = $version;
        $this->languageId = $languageId;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'uri'        => $this->getUri(),
            'languageId' => $this->getLanguageId(),
            'version'    => $this->getVersion(),
            'text'       => $this->getText(),
        ];
    }
}
