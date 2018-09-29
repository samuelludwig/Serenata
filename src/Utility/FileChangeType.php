<?php

namespace Serenata\Utility;

/**
 * Represents the type of {@see FileEvent} objects.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#workspace_didChangeWatchedFiles
 */
final class FileChangeType
{
    /**
     * @var int
     */
    public const CREATED = 1;

    /**
     * @var int
     */
    public const CHANGED = 2;

    /**
     * @var int
     */
    public const DELETED = 3;
}
