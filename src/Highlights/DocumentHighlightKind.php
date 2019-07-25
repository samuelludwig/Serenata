<?php

namespace Serenata\Highlights;

/**
 * Enumeration of symbol kinds.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#textDocument_documentHighlights
 */
final class DocumentHighlightKind
{
    /**
     * @var int
     */
    public const TEXT = 1;

    /**
     * @var int
     */
    public const READ = 2;

    /**
     * @var int
     */
    public const WRITE = 3;
}
