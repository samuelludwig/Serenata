<?php

namespace Serenata\Autocompletion;

use Serenata\Common\Position;

/**
 * Interface for classes that determine the prefix (the part of the word that is being typed) for autocompletion
 * purposes at a specific location.
 */
interface AutocompletionPrefixDeterminerInterface
{
    /**
     * @param string   $source
     * @param Position $position
     *
     * @return string
     */
    public function determine(string $source, Position $position): string;
}
