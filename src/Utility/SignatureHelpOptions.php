<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents initialization parameters.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#initialize
 */
final class SignatureHelpOptions implements JsonSerializable
{
    /**
     * @var string[]|null
     */
    private $triggerCharacters;

    /**
     * @param string[]|null $triggerCharacters
     */
    public function __construct(?array $triggerCharacters)
    {
        $this->triggerCharacters = $triggerCharacters;
    }

    /**
     * @return string[]|null
     */
    public function getTriggerCharacters(): ?array
    {
        return $this->triggerCharacters;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'triggerCharacters' => $this->getTriggerCharacters(),
        ];
    }
}
