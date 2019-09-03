<?php

namespace Serenata\CodeLenses;

use JsonSerializable;

use Serenata\Common\Range;

use Serenata\Utility\CommandInterface;

/**
 * Represents a document highlight.
 *
 * This is a value object and immutable.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#textDocument_codeLens
 */
final class CodeLens implements JsonSerializable
{
    /**
     * @var Range
     */
    private $range;

    /**
     * @var CommandInterface|null
     */
    private $command;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param Range                 $range
     * @param CommandInterface|null $command
     * @param mixed                 $data
     */
    public function __construct(Range $range, ?CommandInterface $command, $data)
    {
        $this->range = $range;
        $this->command = $command;
        $this->data = $data;
    }

    /**
     * @return Range
     */
    public function getRange(): Range
    {
        return $this->range;
    }

    /**
     * @return CommandInterface|null
     */
    public function getCommand(): ?CommandInterface
    {
        return $this->command;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'range'   => $this->getRange(),
            'command' => $this->getCommand(),
            'data'    => $this->getData(),
        ];
    }
}
