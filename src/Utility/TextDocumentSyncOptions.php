<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents initialization parameters.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#initialize
 */
final class TextDocumentSyncOptions implements JsonSerializable
{
    /**
     * @var bool|null
     */
    private $openClose;

    /**
     * @var int|null
     */
    private $change;

    /**
     * @var bool|null
     */
    private $willSave;

    /**
     * @var bool|null
     */
    private $willSaveWaitUntil;

    /**
     * @var SaveOptions|null
     */
    private $save;

    /**
     * @param bool|null        $openClose
     * @param int|null         $change
     * @param bool|null        $willSave
     * @param bool|null        $willSaveWaitUntil
     * @param SaveOptions|null $save
     */
    public function __construct(
        ?bool $openClose,
        ?int $change,
        ?bool $willSave,
        ?bool $willSaveWaitUntil,
        ?SaveOptions $save
    ) {
        $this->openClose = $openClose;
        $this->change = $change;
        $this->willSave = $willSave;
        $this->willSaveWaitUntil = $willSaveWaitUntil;
        $this->save = $save;
    }

    /**
     * @return bool|null
     */
    public function getOpenClose(): ?bool
    {
        return $this->openClose;
    }

    /**
     * @return int|null
     */
    public function getChange(): ?int
    {
        return $this->change;
    }

    /**
     * @return bool|null
     */
    public function getWillSave(): ?bool
    {
        return $this->willSave;
    }

    /**
     * @return bool|null
     */
    public function getWillSaveWaitUntil(): ?bool
    {
        return $this->willSaveWaitUntil;
    }

    /**
     * @return SaveOptions|null
     */
    public function getSave(): ?SaveOptions
    {
        return $this->save;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'openClose'         => $this->getOpenClose(),
            'change'            => $this->getChange(),
            'willSave'          => $this->getWillSave(),
            'willSaveWaitUntil' => $this->getWillSaveWaitUntil(),
            'save'              => $this->getSave(),
        ];
    }
}
