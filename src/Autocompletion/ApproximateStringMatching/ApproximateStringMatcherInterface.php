<?php

namespace Serenata\Autocompletion\ApproximateStringMatching;

/**
 * Interface for classes that can perform approximate string matching.
 */
interface ApproximateStringMatcherInterface
{
    /**
     * @param string $approximation
     * @param string $referenceText
     *
     * @return float|null The score, or null if the parameters are so far apart they don't look anything alike (i.e. a
     *                    threshold has been crossed and the result should not be seen as an approximation as a result).
     *                    The higher the score, the more difference there is between the two strings, thus the worse the
     *                    result.
     */
    public function score(string $approximation, string $referenceText): ?float;
}
