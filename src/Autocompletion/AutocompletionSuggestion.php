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
 */
final class AutocompletionSuggestion implements JsonSerializable, ArrayAccess
{
    /**
     * @var string
     */
    private $filterText;

    /**
     * @var string Item from {@see SuggestionKind}
     */
    private $kind;

    /**
     * @var string|null
     */
    private $insertText;

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
     * @var array
     */
    private $extraData;

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
     * @param string        $kind
     * @param string|null   $insertText
     * @param TextEdit|null $textEdit
     * @param string        $label
     * @param string|null   $documentation
     * @param array         $extraData
     * @param TextEdit[]    $additionalTextEdits
     * @param bool          $deprecated
     * @param string|null   $detail
     */
    public function __construct(
        string $filterText,
        string $kind,
        ?string $insertText,
        ?TextEdit $textEdit,
        string $label,
        ?string $documentation,
        array $extraData = [],
        array $additionalTextEdits = [],
        bool $deprecated = false,
        ?string $detail = null
    ) {
        $this->filterText = $filterText;
        $this->kind = $kind;
        $this->insertText = $insertText;
        $this->textEdit = $textEdit;
        $this->label = $label;
        $this->documentation = $documentation;
        $this->extraData = $extraData;
        $this->additionalTextEdits = $additionalTextEdits;
        $this->deprecated = $deprecated;
        $this->detail = $detail;

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
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @return string|null
     */
    public function getInsertText(): ?string
    {
        return $this->insertText;
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
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
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
    public function jsonSerialize()
    {
        return [
            'filterText'          => $this->getFilterText(),
            'kind'                => $this->getKind(),
            'insertText'          => $this->getInsertText(),
            'textEdit'            => $this->getTextEdit(),
            'label'               => $this->getLabel(),
            'documentation'       => $this->getDocumentation(),

            'extraData' => array_merge($this->getExtraData(), [
                // TODO: Deprecated, kept for backwards compatibility. Remove in next major version.
                'isDeprecated' => $this->getDeprecated(),
            ]),

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
