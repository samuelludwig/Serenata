<?php

namespace Serenata\Highlights;

use JsonSerializable;

use Serenata\Common\Range;

/**
 * Represents a document highlight.
 *
 * This is a value object and immutable.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#textDocument_documentHighlight
 */
final class DocumentHighlight implements JsonSerializable
{
    /**
     * @var Range
     */
    private $range;

    /**
     * @var int|null
     */
    private $kind;

    /**
     * @param Range    $range
     * @param int|null $kind
     */
    public function __construct(Range $range, ?int $kind)
    {
        $this->range = $range;
        $this->kind = $kind;
    }

    /**
     * @return Range
     */
    public function getRange(): Range
    {
        return $this->range;
    }

    /**
     * @return int|null
     */
    public function getKind(): ?int
    {
        return $this->kind;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'range' => $this->getRange(),
            'kind'  => $this->getKind(),
        ];
    }
}
