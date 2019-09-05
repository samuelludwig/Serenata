<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents initialization parameters.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#initialize
 */
final class InitializeResult implements JsonSerializable
{
    /**
     * @var ServerCapabilities
     */
    private $capabilities;

    /**
     * @param ServerCapabilities $capabilities
     */
    public function __construct(ServerCapabilities $capabilities)
    {
        $this->capabilities = $capabilities;
    }

    /**
     * @return ServerCapabilities
     */
    public function getCapabilities(): ServerCapabilities
    {
        return $this->capabilities;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'capabilities' => $this->getCapabilities(),
        ];
    }
}
