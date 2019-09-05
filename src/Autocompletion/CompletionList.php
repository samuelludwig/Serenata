<?php

namespace Serenata\Autocompletion;

use JsonSerializable;

/**
 * Represents a autocompletion list.
 *
 * This is a value object and immutable.
 */
final class CompletionList implements JsonSerializable
{
    /**
     * @var bool
     */
    private $isIncomplete;

    /**
     * @var CompletionItem[]
     */
    private $items;

    /**
     * @param bool             $isIncomplete
     * @param CompletionItem[] $items
     */
    public function __construct(bool $isIncomplete, array $items)
    {
        $this->isIncomplete = $isIncomplete;
        $this->items = $items;
    }

    /**
     * @return bool
     */
    public function isIncomplete(): bool
    {
        return $this->isIncomplete;
    }

    /**
     * @return CompletionItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'isIncomplete' => $this->isIncomplete(),
            'items'        => $this->getItems(),
        ];
    }
}
