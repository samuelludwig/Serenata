<?php

namespace Serenata\Utility;

/**
 * Represents a command that the client can request be executed by the server.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#command
 */
final class Command implements CommandInterface
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
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @inheritDoc
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
