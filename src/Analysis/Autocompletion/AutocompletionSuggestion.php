<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use JsonSerializable;

use PhpIntegrator\Utility\TextEdit;

/**
 * Represents a single autocompletion suggestion.
 *
 * This is a value object and immutable.
 */
final class AutocompletionSuggestion implements JsonSerializable
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
     * @var string
     */
    private $insertText;

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
     * @param string      $filterText
     * @param string      $kind
     * @param string      $insertText
     * @param string      $label
     * @param string|null $documentation
     * @param array       $extraData
     * @param TextEdit[]  $additionalTextEdits
     */
    public function __construct(
        string $filterText,
        string $kind,
        string $insertText,
        string $label,
        ?string $documentation,
        array $extraData = [],
        array $additionalTextEdits = []
    ) {
        $this->filterText = $filterText;
        $this->kind = $kind;
        $this->insertText = $insertText;
        $this->label = $label;
        $this->documentation = $documentation;
        $this->extraData = $extraData;
        $this->additionalTextEdits = $additionalTextEdits;
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
     * @return string
     */
    public function getInsertText(): string
    {
        return $this->insertText;
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
            'label'               => $this->getLabel(),
            'documentation'       => $this->getDocumentation(),
            'extraData'           => $this->getExtraData(),
            'additionalTextEdits' => $this->getAdditionalTextEdits()
        ];
    }
}
