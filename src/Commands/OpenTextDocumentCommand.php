<?php

namespace Serenata\Commands;

use Serenata\Common\Position;

use Serenata\Utility\Command;
use Serenata\Utility\CommandInterface;

/**
 * Enumerates all values for the {@see Command}'s value.
 */
final class OpenTextDocumentCommand implements CommandInterface
{
    /**
     * @var Command
     */
    private $command;

    /**
     * @param string   $title
     * @param string   $uri
     * @param Position $position
     */
    public function __construct(string $title, string $uri, Position $position)
    {
        $this->command = new Command($title, static::getCommandName(), [
            'uri'      => $uri,
            'position' => $position,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->command->getTitle();
    }

    /**
     * @inheritDoc
     */
    public function getCommand(): string
    {
        return $this->command->getCommand();
    }

    /**
     * @inheritDoc
     */
    public function getArguments(): ?array
    {
        return $this->command->getArguments();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->command->jsonSerialize();
    }

    /**
     * @return string
     */
    public static function getCommandName(): string
    {
        return 'serenata/command/openTextDocument';
    }
}
