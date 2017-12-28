<?php

namespace PhpIntegrator\Analysis\Autocompletion\ApproximateStringMatching;

/**
 * Perform approximate string matching using the {@see levenshtein} algorithm and function.
 */
class LevenshteinApproximateStringMatcher implements ApproximateStringMatcherInterface
{
    /**
     * @var int
     */
    private const THRESHOLD = 300;

    /**
     * @inheritDoc
     */
    public function score(string $approximation, string $referenceText): ?float
    {
        $score = levenshtein($referenceText, $approximation, 1, 100, 300);

        if ($score > self::THRESHOLD) {
            return null;
        }

        return $score;
    }
}
