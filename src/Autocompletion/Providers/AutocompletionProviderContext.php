<?php

namespace Serenata\Autocompletion\Providers;

use ArrayAccess;
use LogicException;
use JsonSerializable;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;
use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Context in which autocompletion was requested.
 *
 * Not to be confused with the language server protocol's "CompletionContext" object.
 */
final class AutocompletionProviderContext
{
    /**
     * @var TextDocumentItem
     */
    private $textDocumentItem;

    /**
     * @var Position
     */
    private $position;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     * @param string           $prefix
     */
    public function __construct(TextDocumentItem $textDocumentItem, Position $position, string $prefix)
    {
        $this->textDocumentItem = $textDocumentItem;
        $this->position = $position;
        $this->prefix = $prefix;
    }

    /**
     * @return TextDocumentItem
     */
    public function getTextDocumentItem(): TextDocumentItem
    {
        return $this->textDocumentItem;
    }

    /**
     * @return Position
     */
    public function getPosition(): Position
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return int
     */
    public function getPositionAsByteOffset(): int
    {
        return $this->getPosition()->getAsByteOffsetInString(
            $this->getTextDocumentItem()->getText(),
            PositionEncoding::VALUE
        );
    }

    /**
     * @return Range
     */
    public function getPrefixRange(): Range
    {
        return new Range(
            new Position(
                $this->getPosition()->getLine(),
                $this->getPosition()->getCharacter() - mb_strlen($this->getPrefix())
            ),
            $this->getPosition()
        );
    }
}
