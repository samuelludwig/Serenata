<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use Traversable;

/**
 * Interface for classes that provide autocompletion suggestions at a specific location in a file.
 */
interface AutocompletionProviderInterface
{
    /**
     * @param string $code
     * @param int    $offset
     *
     * @return Traversable Traversable<AutocompletionSuggestion>
     */
    public function provide(string $code, int $offset): Traversable;
}
