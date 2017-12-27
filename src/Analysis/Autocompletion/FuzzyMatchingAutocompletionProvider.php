<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Indexing\Structures\File;

/**
 * Autocompletion provider that delegates to another provider and then fuzzy matches the suggestions based on what was
 * already typed at the requested offset.
 */
final class FuzzyMatchingAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var AutocompletionProviderInterface
     */
    private $delegate;

    /**
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @param AutocompletionProviderInterface         $delegate
     * @param AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
     */
    public function __construct(
        AutocompletionProviderInterface $delegate,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
    ) {
        $this->delegate = $delegate;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $suggestionScorePairList = $this->generateScoredSuggestionPairList($file, $code, $offset);

        usort($suggestionScorePairList, function (array $a, array $b) {
            return $a[1] <=> $b[1];
        });

        return array_column($suggestionScorePairList, 0);
    }

    /**
     * @param File   $file
     * @param string $code
     * @param string $offset
     *
     * @return array[]
     */
    private function generateScoredSuggestionPairList(File $file, string $code, string $offset): array
    {
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        return $this->generateScoredSuggestionPairListForPrefix($file, $code, $offset, $prefix);
    }

    /**
     * @param File   $file
     * @param string $code
     * @param string $offset
     * @param string $prefix
     *
     * @return array[]
     */
    private function generateScoredSuggestionPairListForPrefix(
        File $file,
        string $code,
        string $offset,
        string $prefix
    ): array {
        $suggestions = [];

        /** @var AutocompletionSuggestion $suggestion */
        foreach ($this->delegate->provide($file, $code, $offset) as $suggestion) {
            $suggestions[] = [$suggestion, $this->calculateScore($prefix, $suggestion->getFilterText())];
        }

        return $suggestions;
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    private function calculateScore(string $a, string $b): int
    {
        return levenshtein($a, $b, 1, 50, 1000);
    }
}
