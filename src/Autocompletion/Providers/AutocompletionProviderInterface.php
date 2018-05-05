<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Indexing\Structures\File;

/**
 * Interface for classes that provide autocompletion suggestions at a specific location in a file.
 */
interface AutocompletionProviderInterface
{
    /**
     * @param File   $file
     * @param string $code
     * @param int    $offset
     *
     * @return iterable iterable<AutocompletionSuggestion>
     */
    public function provide(File $file, string $code, int $offset): iterable;
}
