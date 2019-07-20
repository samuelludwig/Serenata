<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents completion options.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#initialize
 */
final class CompletionOptions implements JsonSerializable
{
    /**
     * @var bool|null
     */
    private $resolveProvider;

    /**
     * @var string[]|null
     */
    private $triggerCharacters;

    /**
     * @param bool|null     $resolveProvider
     * @param string[]|null $triggerCharacters
     */
    public function __construct(?bool $resolveProvider, ?array $triggerCharacters)
    {
        $this->resolveProvider = $resolveProvider;
        $this->triggerCharacters = $triggerCharacters;
    }

    /**
     * @return bool|null
     */
    public function getResolveProvider(): ?bool
    {
        return $this->resolveProvider;
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
            'resolveProvider'   => $this->getResolveProvider(),
            'triggerCharacters' => $this->getTriggerCharacters(),
        ];
    }
}
