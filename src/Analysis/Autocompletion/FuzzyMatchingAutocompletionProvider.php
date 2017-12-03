<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Autocompletion provider that delegates to another provider and then fuzzy matches the suggestions based on what was
 * already typed at the requested offset.
 */
final class FuzzyMatchingAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var string[]
     */
    private const BOUNDARY_TOKEN_MAP = [
        " "  => true,
        "\n" => true,
        "\t" => true,
        "("  => true,
        ")"  => true,
        "{"  => true,
        "}"  => true,
        "["  => true,
        "]"  => true,
        "+"  => true,
        "-"  => true,
        "*"  => true,
        "/"  => true,
        "^"  => true,
        "|"  => true,
        "&"  => true,
        ":"  => true,
        "!"  => true,
        "@"  => true,
        "#"  => true,
        "%"  => true
    ];

    /**
     * @var AutocompletionProviderInterface
     */
    private $delegate;

    /**
     * @param AutocompletionProviderInterface $delegate
     */
    public function __construct(AutocompletionProviderInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function provide(string $code, int $offset): iterable
    {
        $suggestionScorePairList = $this->generateScoredSuggestionPairList($code, $offset);

        usort($suggestionScorePairList, function (array $a, array $b) {
            return $a[1] <=> $b[1];
        });

        return array_column($suggestionScorePairList, 0);
    }

    /**
     * @param string $code
     * @param string $offset
     *
     * @return array[]
     */
    private function generateScoredSuggestionPairList(string $code, string $offset): array
    {
        $prefix = $this->getPrefixAtOffset($code, $offset);

        return $this->generateScoredSuggestionPairListForPrefix($code, $offset, $prefix);
    }

    /**
     * @param string $code
     * @param string $offset
     * @param string $prefix
     *
     * @return array[]
     */
    private function generateScoredSuggestionPairListForPrefix(string $code, string $offset, string $prefix): array
    {
        $suggestions = [];

        /** @var AutocompletionSuggestion $suggestion */
        foreach ($this->delegate->provide($code, $offset) as $suggestion) {
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
        // TODO: Up the cost of deletion?
        return levenshtein($a, $b);
    }

    /**
     * @param string $code
     * @param int    $offset
     *
     * @return string
     */
    private function getPrefixAtOffset(string $code, int $offset): string
    {
        $i = max($offset - 1, 0);

        while ($i > 0) {
            if (isset(self::BOUNDARY_TOKEN_MAP[$code[$i]])) {
                ++$i; // Don't include the boundary character itself.
                break;
            }

            --$i;
        }

        return substr($code, $i, $offset - $i);
    }
}
