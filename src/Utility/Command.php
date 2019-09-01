<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents a location inside a resource.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#command
 */
final class Command implements JsonSerializable
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $command;

    /**
     * @var mixed[]|null
     */
    private $arguments;

    /**
     * @param string       $title
     * @param string       $command
     * @param mixed[]|null $arguments
     */
    public function __construct(string $title, string $command, ?array $arguments)
    {
        $this->title = $title;
        $this->command = $command;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return mixed[]|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'title'     => $this->getTitle(),
            'command'   => $this->getCommand(),
            'arguments' => $this->getArguments(),
        ];
    }
}
