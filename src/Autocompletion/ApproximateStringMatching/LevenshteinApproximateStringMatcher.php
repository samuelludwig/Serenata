<?php

namespace PhpIntegrator\Autocompletion\ApproximateStringMatching;

/**
 * Perform approximate string matching using the {@see levenshtein} algorithm and function.
 */
class LevenshteinApproximateStringMatcher implements ApproximateStringMatcherInterface
{
    /**
     * @var int
     */
    private const MAX_LENGTH_IN_BYTES = 255;

    /**
     * @var int
     */
    private const INSERTION_COST = self::MAX_LENGTH_IN_BYTES;

    /**
     * Cost of a replacement.
     *
     * This multiplication is required as the cost of having to insert MAX_LENGTH of characters to get the approximation
     * is still better than replacing a single character. In other words, being able to complete the current word
     * without replacing any of its characters is the optimal situation.
     *
     * @var int
     */
    private const REPLACEMENT_COST = self::INSERTION_COST * self::MAX_LENGTH_IN_BYTES;

    /**
     * Cost of a removal.
     *
     * This multiplication makes removals somewhat less desirable than replacements. In other words, strings that look
     * a lot like the reference text, but only require some characters to be swapped, are preferred over those where
     * characters need to actually be removed.
     *
     * @var int
     */
    private const REMOVAL_COST = self::REPLACEMENT_COST * 3;

    /**
     * @var int
     */
    private const THRESHOLD = self::REMOVAL_COST;

    /**
     * @inheritDoc
     */
    public function score(string $approximation, string $referenceText): ?float
    {
        // We need to exit early for these to avoid incorrect results, see below.
        if (strlen($approximation) > self::MAX_LENGTH_IN_BYTES) {
            return null;
        } elseif (strlen($referenceText) > self::MAX_LENGTH_IN_BYTES) {
            return null;
        } elseif ($referenceText === '') {
            return null; // Nothing can match this, except another empty string, which is pretty useless on its own.
        }

        return $this->isSubstringMatch($approximation, $referenceText) ?
            $this->calculateSubstringMatchScore($approximation, $referenceText) :
            $this->calculateFuzzyMatchScore($approximation, $referenceText);
    }

    /**
     * @param string $approximation
     * @param string $referenceText
     *
     * @return bool
     */
    private function isSubstringMatch(string $approximation, string $referenceText): bool
    {
        $referenceTextLength = strlen($referenceText);
        $approximationLength = strlen($approximation);

        if ($referenceTextLength > $approximationLength) {
            return 0;
        }

        $bonus = 0;
        $scanEnd = ($approximationLength - $referenceTextLength);

        for ($i = 0; $i <= $scanEnd; ++$i) {
            if (substr($approximation, $i, $referenceTextLength) === $referenceText) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $approximation
     * @param string $referenceText
     *
     * @return float
     */
    private function calculateSubstringMatchScore(string $approximation, string $referenceText): float
    {
        return
            (self::MAX_LENGTH_IN_BYTES * 3) -
            $this->calculateBonusForBoundarySubstringMatches($approximation, $referenceText) +
            (strlen($approximation) - strlen($referenceText));
    }

    /**
     * @param string $approximation
     * @param string $referenceText
     *
     * @return float|null
     */
    private function calculateFuzzyMatchScore(string $approximation, string $referenceText): ?float
    {
        $score = @levenshtein(
            $referenceText,
            $approximation,
            self::INSERTION_COST,
            self::REPLACEMENT_COST,
            self::REMOVAL_COST
        );

        if ($score === -1 || $score > self::THRESHOLD) {
            return null;
        }

        return $score;
    }

    /**
     * Calculates a bonus to assign if substring matches are surrounded by word boundaries.
     *
     * @example matching "A" against "A\B" gets a bonus because the former is part of the latter and followed by "\\"
     * @example matching "B" against "A\B" gets a bonus because the former is part of the latter and preceded by "\\"
     * @example matching "B" against "ABC" does not get a bonus
     *
     * @param string $approximation
     * @param string $referenceText
     *
     * @return float
     */
    private function calculateBonusForBoundarySubstringMatches(string $approximation, string $referenceText): float
    {
        $approximationLength = strlen($approximation);
        $referenceTextLength = strlen($referenceText);

        $i = strpos($approximation, $referenceText);

        assert($i !== false, "Can't calculate bonus for substring matches as there is no substring match");

        $bonus = 0;

        if ($i === 0 || $approximation[$i - 1] === '\\') {
            $bonus += self::MAX_LENGTH_IN_BYTES;
        }

        if (($i + $referenceTextLength) >= $approximationLength ||
            $approximation[$i + $referenceTextLength] === '\\'
        ) {
            $bonus += self::MAX_LENGTH_IN_BYTES;
        }

        return $bonus;
    }
}
