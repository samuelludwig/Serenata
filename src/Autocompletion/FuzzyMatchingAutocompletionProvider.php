<?php

namespace PhpIntegrator\Autocompletion;

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
     * @var ApproximateStringMatching\BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param AutocompletionProviderInterface                                      $delegate
     * @param AutocompletionPrefixDeterminerInterface                              $autocompletionPrefixDeterminer
     * @param ApproximateStringMatching\BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param int                                                                  $resultLimit
     */
    public function __construct(
        AutocompletionProviderInterface $delegate,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer,
        ApproximateStringMatching\BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        int $resultLimit
    ) {
        $this->delegate = $delegate;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        return $this->bestStringApproximationDeterminer->determine(
            $this->delegate->provide($file, $code, $offset),
            $this->autocompletionPrefixDeterminer->determine($code, $offset),
            'filterText',
            $this->resultLimit
        );
    }
}
