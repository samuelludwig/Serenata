<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents parameters that can be passed when logging messages.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#window_logMessage
 */
final class LogMessageParams implements JsonSerializable
{
    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $message;

    /**
     * @param int    $type
     * @param string $message
     */
    public function __construct(int $type, string $message)
    {
        $this->type = $type;
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'type'    => $this->getType(),
            'message' => $this->getMessage(),
        ];
    }
}
