<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents save options for {@see TextDocumentSyncOptions} initialization parameters.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#initialize
 */
final class SaveOptions implements JsonSerializable
{
    /**
     * @var bool|null
     */
    private $includeText;

    /**
     * @param bool|null $includeText
     */
    public function __construct(?bool $includeText)
    {
        $this->includeText = $includeText;
    }

    /**
     * @return bool|null
     */
    public function getIncludeText(): ?bool
    {
        return $this->includeText;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'includeText' => $this->getIncludeText(),
        ];
    }
}
