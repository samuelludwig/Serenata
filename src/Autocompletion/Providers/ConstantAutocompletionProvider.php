<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Analysis\ConstantListProviderInterface;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\AutocompletionSuggestionTypeFormatter;
use Serenata\Autocompletion\AutocompletionPrefixDeterminerInterface;

use Serenata\Indexing\Structures\File;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

/**
 * Provides constant autocompletion suggestions at a specific location in a file.
 */
final class ConstantAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var ConstantListProviderInterface
     */
    private $constantListProvider;

    /**
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @var BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var AutocompletionSuggestionTypeFormatter
     */
    private $autocompletionSuggestionTypeFormatter;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param ConstantListProviderInterface              $constantListProvider
     * @param AutocompletionPrefixDeterminerInterface    $autocompletionPrefixDeterminer
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param AutocompletionSuggestionTypeFormatter      $autocompletionSuggestionTypeFormatter
     * @param int                                        $resultLimit
     */
    public function __construct(
        ConstantListProviderInterface $constantListProvider,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter,
        int $resultLimit
    ) {
        $this->constantListProvider = $constantListProvider;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->autocompletionSuggestionTypeFormatter = $autocompletionSuggestionTypeFormatter;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $this->constantListProvider->getAll(),
            $this->autocompletionPrefixDeterminer->determine($code, $offset),
            'name',
            $this->resultLimit
        );

        foreach ($bestApproximations as $constant) {
            yield $this->createSuggestion($constant);
        }
    }

    /**
     * @param array $constant
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $constant): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            $constant['name'],
            SuggestionKind::CONSTANT,
            $constant['name'],
            null,
            $constant['name'],
            $constant['shortDescription'],
            [
                'isDeprecated' => $constant['isDeprecated'],
                'returnTypes'  => $this->autocompletionSuggestionTypeFormatter->format($constant['types'])
            ]
        );
    }
}
