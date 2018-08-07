<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

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
     * @var BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param AutocompletionProviderInterface            $delegate
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param int                                        $resultLimit
     */
    public function __construct(
        AutocompletionProviderInterface $delegate,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        int $resultLimit
    ) {
        $this->delegate = $delegate;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        return $this->bestStringApproximationDeterminer->determine(
            $this->delegate->provide($context),
            $context->getPrefix(),
            'filterText',
            $this->resultLimit
        );
    }
}
