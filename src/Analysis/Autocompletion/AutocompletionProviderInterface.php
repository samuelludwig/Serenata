<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Interface for classes that provide autocompletion suggestions at a specific location in a file.
 */
interface AutocompletionProviderInterface
{
    /**
     * @param string $code
     * @param int    $offset
     *
     * @return AutocompletionSuggestion[]
     */
    public function provide(string $code, int $offset): array;
}
