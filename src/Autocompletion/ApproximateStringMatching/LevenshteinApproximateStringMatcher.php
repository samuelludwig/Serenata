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
        $approximationLength = strlen($approximation);
        $referenceTextLength = strlen($referenceText);

        // We need to exit early for these to avoid incorrect results, see below.
        if ($approximationLength > self::MAX_LENGTH_IN_BYTES) {
            return null;
        } elseif ($referenceTextLength > self::MAX_LENGTH_IN_BYTES) {
            return null;
        }

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

        return $score - $this->calculateBonusForSubstringMatches($approximation, $referenceText);
    }

    /**
     * @param string $approximation
     * @param string $referenceText
     *
     * @return float
     */
    private function calculateBonusForSubstringMatches(string $approximation, string $referenceText): float
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
                $bonus += self::REPLACEMENT_COST * 5;
                break;
            }
        }

        return $bonus;
    }
}
