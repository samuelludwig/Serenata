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
        "?"  => true,
        "@"  => true,
        "#"  => true,
        "%"  => true,
        ">"  => true,
        "<"  => true,
        "="  => true,
        "\\" => true
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
    public function provide(File $file, string $code, int $offset): iterable
    {
        return $this->provideForPrefixAtOffset($file, $code, $offset);
    }

    /**
     * @param File   $file
     * @param string $code
     * @param string $offset
     *
     * @return array[]
     */
    private function provideForPrefixAtOffset(File $file, string $code, string $offset): array
    {
        $prefix = $this->getPrefixAtOffset($code, $offset);

        return $this->provideForPrefix($file, $code, $offset, $prefix);
    }

    /**
     * @param File   $file,
     * @param string $code
     * @param string $offset
     * @param string $prefix
     *
     * @return array[]
     */
    private function provideForPrefix(File $file, string $code, string $offset, string $prefix): array
    {
        $suggestionsArray = [];

        foreach ($this->delegate->provide($file, $code, $offset) as $suggestion) {
            $suggestionsArray[] = $suggestion;
        }

        if ($prefix === '') {
            return $suggestionsArray;
        } elseif (empty($suggestionsArray)) {
            return [];
        }

        $fuse = new \Fuse\Fuse($suggestionsArray, [
            // See also https://github.com/Loilo/Fuse#options
            'shouldSort'       => true,
            'caseSensitive'    => false,
            'threshold'        => 0.25,
            'maxPatternLength' => 128,
            'keys'             => ['getFilterText'],

            'getFn' => function (AutocompletionSuggestion $item, string $path) {
                return $item->{$path}();
            }
        ]);

        return $fuse->search($prefix);
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
