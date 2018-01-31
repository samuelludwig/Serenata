<?php

namespace PhpIntegrator\Autocompletion\ApproximateStringMatching;

use ArrayAccess;

/**
 * Interface for classes that perform string approximation on a list of items and return only the best result(s).
 */
interface BestStringApproximationDeterminerInterface
{
    /**
     * @param iterable $items         Iterable of items (nested associative arrays or objects implementing ArrayAccess)
     *                                that are to be evaluated.
     * @param string   $referenceText The text to determine the approximation to for each result.
     * @param string   $itemValueKey  The key to search for in each item and to use for the approximation.
     * @param int|null $limit         The optional maximum amount of items to return. Lowering this value may improve
     *                                performance.
     *
     * @return (array|ArrayAccess)[] The same list of items received as input, but filtered and sorted due to the
     *                               approximation.
     */
    public function determine(iterable $items, string $referenceText, string $itemValueKey, ?int $limit): array;
}
