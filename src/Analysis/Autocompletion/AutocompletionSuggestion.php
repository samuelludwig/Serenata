<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use ArrayAccess;
use AssertionError;
use JsonSerializable;

use PhpIntegrator\Utility\TextEdit;

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
     * @param string        $filterText
     * @param string        $kind
     * @param string|null   $insertText
     * @param TextEdit|null $textEdit
     * @param string        $label
     * @param string|null   $documentation
     * @param array         $extraData
     * @param TextEdit[]    $additionalTextEdits
     */
    public function __construct(
        string $filterText,
        string $kind,
        ?string $insertText,
        ?TextEdit $textEdit,
        string $label,
        ?string $documentation,
        array $extraData = [],
        array $additionalTextEdits = []
    ) {
        $this->filterText = $filterText;
        $this->kind = $kind;
        $this->insertText = $insertText;
        $this->textEdit = $textEdit;
        $this->label = $label;
        $this->documentation = $documentation;
        $this->extraData = $extraData;
        $this->additionalTextEdits = $additionalTextEdits;

        if ($insertText === null && $textEdit === null) {
            throw new AssertionError('Either an insertText or a textEdit must be provided');
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
            'extraData'           => $this->getExtraData(),
            'additionalTextEdits' => $this->getAdditionalTextEdits()
        ];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        throw new AssertionError('Setting properties directly is not allowed, use setters instead');
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
        throw new AssertionError('Unsetting properties is not allowed');
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->jsonSerialize()[$offset];
    }
}
