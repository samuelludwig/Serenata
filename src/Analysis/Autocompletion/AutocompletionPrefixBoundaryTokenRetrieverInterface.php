<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Interface for classes that retrieve tokens that should act as boundary tokens for autocompletion prefixes (the part
 * of the word that is being typed).
 */
interface AutocompletionPrefixBoundaryTokenRetrieverInterface
{
    /**
     * @return string[]
     */
    public function retrieve(): array;
}
