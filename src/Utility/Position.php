<?php

namespace PhpIntegrator\Utility;

use JsonSerializable;

/**
 * Describes a position in a document.
 *
 * This is a value object and immutable.
 *
 * @see https://github.com/Microsoft/language-server-protocol/blob/master/versions/protocol-2-x.md#position
 */
class Position implements JsonSerializable
{
    /**
     * The line, starting from zero.
     *
     * @var int
     */
    private $line;

    /**
     * The character index on the line, starting from zero.
     *
     * This is a character index, i.e. not byte offsets and "support" Unicode.
     *
     * @var int
     */
    private $character;

    /**
     * @param int $line
     * @param int $character
     */
    public function __construct(int $line, int $character)
    {
        $this->line = $line;
        $this->character = $character;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return int
     */
    public function getCharacter(): int
    {
        return $this->character;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'start' => $this->getStart(),
            'end'   => $this->getEnd()
        ];
    }
}
