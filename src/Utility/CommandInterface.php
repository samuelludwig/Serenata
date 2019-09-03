<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents a command that the client can request be executed by the server.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#command
 */
interface CommandInterface extends JsonSerializable
{
    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return string
     */
    public function getCommand(): string;

    /**
     * @return mixed[]|null
     */
    public function getArguments(): ?array;
}
