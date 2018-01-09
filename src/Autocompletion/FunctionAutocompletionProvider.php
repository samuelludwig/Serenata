<?php

namespace PhpIntegrator\Autocompletion;

use PhpIntegrator\Analysis\FunctionListProviderInterface;

use PhpIntegrator\Indexing\Structures\File;

/**
 * Provides function autocompletion suggestions at a specific location in a file.
 */
final class FunctionAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @var ApproximateStringMatching\BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var FunctionAutocompletionSuggestionLabelCreator
     */
    private $functionAutocompletionSuggestionLabelCreator;

    /**
     * @var FunctionAutocompletionSuggestionParanthesesNecessityEvaluator
     */
    private $functionAutocompletionSuggestionParanthesesNecessityEvaluator;

    /**
     * @var AutocompletionSuggestionTypeFormatter
     */
    private $autocompletionSuggestionTypeFormatter;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param FunctionListProviderInterface                                        $functionListProvider
     * @param AutocompletionPrefixDeterminerInterface                              $autocompletionPrefixDeterminer
     * @param ApproximateStringMatching\BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param FunctionAutocompletionSuggestionLabelCreator                         $functionAutocompletionSuggestionLabelCreator
     * @param FunctionAutocompletionSuggestionParanthesesNecessityEvaluator        $functionAutocompletionSuggestionParanthesesNecessityEvaluator
     * @param AutocompletionSuggestionTypeFormatter                                $autocompletionSuggestionTypeFormatter
     * @param int                                                                  $resultLimit
     */
    public function __construct(
        FunctionListProviderInterface $functionListProvider,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer,
        ApproximateStringMatching\BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        FunctionAutocompletionSuggestionLabelCreator $functionAutocompletionSuggestionLabelCreator,
        FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator,
        AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter,
        int $resultLimit
    ) {
        $this->functionListProvider = $functionListProvider;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->functionAutocompletionSuggestionLabelCreator = $functionAutocompletionSuggestionLabelCreator;
        $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator = $functionAutocompletionSuggestionParanthesesNecessityEvaluator;
        $this->autocompletionSuggestionTypeFormatter = $autocompletionSuggestionTypeFormatter;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $shouldIncludeParanthesesInInsertText = $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator
            ->evaluate($code, $offset);

        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $this->functionListProvider->getAll(),
            $this->autocompletionPrefixDeterminer->determine($code, $offset),
            'name',
            $this->resultLimit
        );

        foreach ($bestApproximations as $function) {
            yield $this->createSuggestion($function, $shouldIncludeParanthesesInInsertText);
        }
    }

    /**
     * @param array $function
     * @param bool  $shouldIncludeParanthesesInInsertText
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $function,
        bool $shouldIncludeParanthesesInInsertText
    ): AutocompletionSuggestion {
        $insertText = $function['name'];
        $placeCursorBetweenParentheses = !empty($function['parameters']);

        if ($shouldIncludeParanthesesInInsertText) {
            $insertText .= '()';
        }

        return new AutocompletionSuggestion(
            $function['name'],
            SuggestionKind::FUNCTION,
            $insertText,
            null,
            $this->functionAutocompletionSuggestionLabelCreator->create($function),
            $function['shortDescription'],
            [
                'isDeprecated'                  => $function['isDeprecated'],
                'returnTypes'                   => $this->autocompletionSuggestionTypeFormatter->format($function['returnTypes']),
                'placeCursorBetweenParentheses' => $placeCursorBetweenParentheses
            ]
        );
    }
}
