<?php

namespace Serenata\Commands;

/**
 * Enumeration of command names that can be sent to clients (i.e. are not handled by the server, but by clients).
 */
final class ClientCommandName
{
    /**
     * @var string
     */
    public const OPEN_TEXT_DOCUMENT = 'serenata/openTextDocument';
}
