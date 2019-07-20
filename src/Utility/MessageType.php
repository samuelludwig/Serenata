<?php

namespace Serenata\Utility;

/**
 * Represents the type of logged or shown messages.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#window_showMessage
 */
final class MessageType
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
    public const INFO = 3;

    /**
     * @var int
     */
    public const LOG = 4;
}
