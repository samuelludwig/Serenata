<?php

namespace Serenata\Linting;

/**
 * Enumeration of diagnostic severity levels.
 */
final class DiagnosticSeverity
{
    /**
     * @var int
     */
    public const ERROR = 1;

    /**
     * @var int
     */
    public const WARNING = 2;

    /**
     * @var int
     */
    public const INFORMATION = 3;

    /**
     * @var int
     */
    public const HINT = 4;
}
