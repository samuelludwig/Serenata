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
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        return $this->provideForPrefix($file, $code, $offset, $prefix);
    }

    /**
     * @param File   $file
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
}
