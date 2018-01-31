<?php

namespace PhpIntegrator\Utility;

use JsonSerializable;

use PhpIntegrator\Common\Range;

/**
 * Represents a textual edit to a document.
 */
final class TextEdit implements JsonSerializable
{
    /**
     * @var Range
     */
    private $range;

    /**
     * @var string
     */
    private $newText;

    /**
     * @param Range  $range
     * @param string $newText
     */
    public function __construct(Range $range, string $newText)
    {
        $this->range = $range;
        $this->newText = $newText;
    }

    /**
     * @return Range
     */
    public function getRange(): Range
    {
        return $this->range;
    }

    /**
     * @return string
     */
    public function getNewText(): string
    {
        return $this->newText;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'range'   => $this->getRange(),
            'newText' => $this->getNewText()
        ];
    }
}
