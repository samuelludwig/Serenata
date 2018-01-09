<?php

namespace PhpIntegrator\Autocompletion;

/**
 * Interface for classes that determine the prefix (the part of the word that is being typed) for autocompletion
 * purposes at a specific location.
 */
interface AutocompletionPrefixDeterminerInterface
{
    /**
     * @param string $code
     * @param int    $offset
     *
     * @return string
     */
    public function determine(string $code, int $offset): string;
}
