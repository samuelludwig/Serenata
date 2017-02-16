<?php

namespace PhpIntegrator\Utility;

use JsonSerializable;

/**
 * Describes a range in a document.
 *
 * This is a value object and immutable.
 *
 * @see https://github.com/Microsoft/language-server-protocol/blob/master/versions/protocol-2-x.md#range
 */
class Range implements JsonSerializable
{
    /**
     * The (inclusive, starting from zero) start position.
     *
     * @var Position
     */
    protected $start;

    /**
    * The (exclusive, starting from zero) end position.
    *
     * @var Position
     */
    protected $end;

    /**
     * @param Position $start
     * @param Position $end
     */
    public function __construct(Position $start, Position $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return Position
     */
    public function getStart(): Position
    {
        return $this->start;
    }

    /**
     * @return Position
     */
    public function getEnd(): Position
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
