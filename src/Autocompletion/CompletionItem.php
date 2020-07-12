<?php

namespace Serenata\Autocompletion;

use ArrayAccess;
use LogicException;
use JsonSerializable;

use Serenata\Utility\TextEdit;

/**
 * Represents a single autocompletion suggestion.
 *
 * This is a value object and immutable.
 *
 * @implements ArrayAccess<string,mixed>
 */
final class CompletionItem implements JsonSerializable, ArrayAccess
{
    /**
     * @var string
     */
    private $filterText;

    /**
     * @var int|null Item from {@see CompletionItemKind}
     */
    private $kind;

    /**
     * @var int[]|null Zero, one, or more items from {@see CompletionItemTag}
     */
    private $tags;

    /**
     * @var string|null
     */
    private $insertText;

    /**
     * @var string|null
     */
    private $sortText;

    /**
     * @var int|null
     */
    private $insertTextFormat = 2;

    /**
     * @var TextEdit|null
     */
    private $textEdit;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string|null
     */
    private $documentation;

    /**
     * @var TextEdit[]
     */
    private $additionalTextEdits;

    /**
     * @var bool
     */
    private $deprecated;

    /**
     * @var string|null
     */
    private $detail;

    /**
     * @param string        $filterText
     * @param int|null      $kind
     * @param string|null   $insertText
     * @param TextEdit|null $textEdit
     * @param string        $label
     * @param string|null   $documentation
     * @param TextEdit[]    $additionalTextEdits
     * @param bool          $deprecated
     * @param string|null   $detail
     * @param string|null   $sortText
     * @param int[]|null    $tags
     */
    public function __construct(
        string $filterText,
        ?int $kind,
        ?string $insertText,
        ?TextEdit $textEdit,
        string $label,
        ?string $documentation,
        array $additionalTextEdits = [],
        bool $deprecated = false,
        ?string $detail = null,
        ?string $sortText = null,
        ?array $tags = null
    ) {
        $this->filterText = $filterText;
        $this->kind = $kind;
        $this->insertText = $insertText;
        $this->textEdit = $textEdit;
        $this->label = $label;
        $this->documentation = $documentation;
        $this->additionalTextEdits = $additionalTextEdits;
        $this->deprecated = $deprecated;
        $this->detail = $detail;
        $this->sortText = $sortText;

        if ($tags === null && $deprecated) {
            $this->tags = [CompletionItemTag::DEPRECATED];
        } else {
            $this->tags = $tags;
        }

        if ($insertText === null && $textEdit === null) {
            throw new LogicException('Either an insertText or a textEdit must be provided');
        }
    }

    /**
     * @return string
     */
    public function getFilterText(): string
    {
        return $this->filterText;
    }

    /**
     * @return int|null
     */
    public function getKind(): ?int
    {
        return $this->kind;
    }

    /**
     * @return int[]|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * @return string|null
     */
    public function getInsertText(): ?string
    {
        return $this->insertText;
    }

    /**
     * @return int|null
     */
    public function getInsertTextFormat(): ?int
    {
        return $this->insertTextFormat;
    }

    /**
     * @return string|null
     */
    public function getSortText(): ?string
    {
        return $this->sortText;
    }

    /**
     * @return TextEdit|null
     */
    public function getTextEdit(): ?TextEdit
    {
        return $this->textEdit;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string|null
     */
    public function getDocumentation(): ?string
    {
        return $this->documentation;
    }

    /**
     * @return TextEdit[]
     */
    public function getAdditionalTextEdits(): array
    {
        return $this->additionalTextEdits;
    }

    /**
     * @return bool
     */
    public function getDeprecated(): bool
    {
        return $this->deprecated;
    }

    /**
     * @return string|null
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'filterText'          => $this->getFilterText(),
            'kind'                => $this->getKind(),
            'tags'                => $this->getTags(),
            'insertText'          => $this->getInsertText(),
            'insertTextFormat'    => $this->getInsertTextFormat(),
            'sortText'            => $this->getSortText(),
            'textEdit'            => $this->getTextEdit(),
            'label'               => $this->getLabel(),
            'documentation'       => $this->getDocumentation(),
            'additionalTextEdits' => $this->getAdditionalTextEdits(),
            'deprecated'          => $this->getDeprecated(),
            'detail'              => $this->getDetail(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('Setting properties directly is not allowed, use setters instead');
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        $array = $this->jsonSerialize();

        return isset($array[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('Unsetting properties is not allowed');
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->jsonSerialize()[$offset];
    }
}
