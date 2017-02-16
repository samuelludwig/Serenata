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
    protected $line;

    /**
     * The character index on the line, starting from zero.
     *
     * This is a character index, i.e. not byte offsets and "support" Unicode.
     *
     * @var int
     */
    protected $character;

    /**
     * @param int $start
     * @param int $end
     */
    public function __construct(int $start, int $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getEnd(): int
    {
        return $this->end;
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
