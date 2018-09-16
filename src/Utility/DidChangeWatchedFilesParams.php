<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents parameters for the workspace/didChangeWatchedFiles request.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#workspace_didChangeWatchedFiles
 */
final class DidChangeWatchedFilesParams implements JsonSerializable
{
    /**
     * @var FileEvent[]
     */
    private $changes;

    /**
     * @param FileEvent[] $changes
     */
    public function __construct(array $changes)
    {
        $this->changes = $changes;
    }

    /**
     * @return FileEvent[]
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'changes' => $this->getChanges(),
        ];
    }
}
