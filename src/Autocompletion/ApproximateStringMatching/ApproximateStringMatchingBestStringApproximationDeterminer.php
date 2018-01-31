<?php

namespace PhpIntegrator\Autocompletion\ApproximateStringMatching;

/**
 * Performs string approximation on a list of items and return only the best result(s) by using a
 * {@see ApproximateStringMatcherInterface}.
 */
class ApproximateStringMatchingBestStringApproximationDeterminer implements BestStringApproximationDeterminerInterface
{
    /**
     * @var ApproximateStringMatcherInterface
     */
    private $approximateStringMatcher;

    /**
     * @param ApproximateStringMatcherInterface $approximateStringMatcher
     */
    public function __construct(ApproximateStringMatcherInterface $approximateStringMatcher)
    {
        $this->approximateStringMatcher = $approximateStringMatcher;
    }

    /**
     * @inheritDoc
     */
    public function determine(iterable $items, string $referenceText, string $itemValueKey, ?int $limit): array
    {
        // For an input set of ~5000 items, the approximate string matcher may decide that there aren't many of them
        // that can be filtered out, which leaves us with a set of the same amount that we need to sort by score, only
        // to fetch the best n items afterwards. It turns out that just sorting these ~5000 items by a simple comparison
        // function has abysmal performance (around ~60 ms on my, rather high end at the time of writing, hardware).
        //
        // By partitioning n items in nested arrays in an associative array (internally a hash map in PHP) by their
        // score, we can limit the sorting to the m partitions, of which there are usually considerably fewer than
        // actual items. In fact, we could state that m <= n.
        //
        // After the partition sorting, we proceed to fetch only the requested x results from the best partitions.
        // Finally, we sort the resulting, usually limited set of x items, again by the actual criteria and we're done.
        $partitions = $this->partitionByScore($items, $itemValueKey, $referenceText);
        $partitions = $this->pruneUndesiredPartitions($partitions);
        $partitions = $this->sortPartitionsByHighestScore($partitions);

        $bestMatchScorePairs = $this->retrieveBestMatchScorePairsFromPartitions($partitions, $limit);
        $bestMatchScorePairs = $this->sortBestMatchScorePairs($bestMatchScorePairs);

        return $this->extractMatchesFromBestMatchScorePairs($bestMatchScorePairs);
    }

    /**
     * @param iterable $items
     * @param string   $itemValueKey
     * @param string   $referenceText
     *
     * @return array
     */
    private function partitionByScore(iterable $items, string $itemValueKey, string $referenceText): array
    {
        $partitions = [];

        foreach ($items as $item) {
            $score = $this->approximateStringMatcher->score($item[$itemValueKey], $referenceText);

            if (!isset($partitions[$score])) {
                $partitions[$score] = [];
            }

            $partitions[$score][] = $item;
        }

        return $partitions;
    }

    /**
     * @param array $partitions
     *
     * @return array
     */
    private function pruneUndesiredPartitions(array $partitions): array
    {
        // Filter out matches that don't look anything like the reference text.
        unset($partitions[null]);

        return $partitions;
    }

    /**
     * @param array $partitions
     *
     * @return array
     */
    private function sortPartitionsByHighestScore(array $partitions): array
    {
        ksort($partitions);

        return $partitions;
    }

    /**
     * @param array    $partitions
     * @param int|null $limit
     *
     * @return array
     */
    private function retrieveBestMatchScorePairsFromPartitions(array $partitions, ?int $limit): array
    {
        $results = [];

        foreach ($partitions as $score => $matches) {
            foreach ($matches as $match) {
                $results[] = [$match, $score];

                if ($limit !== null && count($results) === $limit) {
                    return $results;
                }
            }
        }

        return $results;
    }

    /**
     * @param array $bestMatchScorePairs
     *
     * @return array
     */
    private function sortBestMatchScorePairs(array $bestMatchScorePairs): array
    {
        usort($bestMatchScorePairs, function (array $a, array $b) {
            return $a[1] <=> $b[1];
        });

        return $bestMatchScorePairs;
    }

    /**
     * @param array $bestMatchScorePairs
     *
     * @return array
     */
    private function extractMatchesFromBestMatchScorePairs(array $bestMatchScorePairs): array
    {
        return array_column($bestMatchScorePairs, 0);
    }
}
