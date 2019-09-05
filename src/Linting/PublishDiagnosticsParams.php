<?php

namespace Serenata\Linting;

use JsonSerializable;

/**
 * Represents a diagnostic inside a resource.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#textDocument_publishDiagnostics
 */
final class PublishDiagnosticsParams implements JsonSerializable
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var Diagnostic[]
     */
    private $diagnostics;

    /**
     * @param string       $uri
     * @param Diagnostic[] $diagnostics
     */
    public function __construct(string $uri, array $diagnostics)
    {
        $this->uri = $uri;
        $this->diagnostics = $diagnostics;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return Diagnostic[]
     */
    public function getDiagnostics(): array
    {
        return $this->diagnostics;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'uri'         => $this->getUri(),
            'diagnostics' => $this->getDiagnostics(),
        ];
    }
}
