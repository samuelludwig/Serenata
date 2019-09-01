<?php

namespace Serenata\CodeLenses;

use JsonSerializable;

use Serenata\Common\Range;

use Serenata\Utility\Command;

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
     * @var Command|null
     */
    private $command;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param Range        $range
     * @param Command|null $command
     * @param mixed        $data
     */
    public function __construct(Range $range, ?Command $command, $data)
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
     * @return Command|null
     */
    public function getCommand(): ?Command
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
