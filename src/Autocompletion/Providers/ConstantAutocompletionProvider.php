<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Analysis\ConstantListProviderInterface;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;
use Serenata\Autocompletion\AutocompletionSuggestionTypeFormatter;


use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

use Serenata\Utility\TextEdit;

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
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param AutocompletionSuggestionTypeFormatter      $autocompletionSuggestionTypeFormatter
     * @param int                                        $resultLimit
     */
    public function __construct(
        ConstantListProviderInterface $constantListProvider,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter,
        int $resultLimit
    ) {
        $this->constantListProvider = $constantListProvider;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->autocompletionSuggestionTypeFormatter = $autocompletionSuggestionTypeFormatter;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        /** @var array[] $bestApproximations */
        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $this->constantListProvider->getAll(),
            $context->getPrefix(),
            'name',
            $this->resultLimit
        );

        foreach ($bestApproximations as $constant) {
            yield $this->createSuggestion($constant, $context);
        }
    }

    /**
     * @param array                         $constant
     * @param AutocompletionProviderContext $context
     *
     * @return CompletionItem
     */
    private function createSuggestion(array $constant, AutocompletionProviderContext $context): CompletionItem
    {
        return new CompletionItem(
            $constant['name'],
            CompletionItemKind::CONSTANT,
            $constant['name'],
            $this->getTextEditForSuggestion($constant, $context),
            $constant['name'],
            $constant['shortDescription'],
            [
                'returnTypes'  => $this->autocompletionSuggestionTypeFormatter->format($constant['types']),
            ],
            [],
            $constant['isDeprecated']
        );
    }

    /**
     * Generate a {@see TextEdit} for the suggestion.
     *
     * Some clients automatically determine the prefix to replace on their end (e.g. Atom) and just paste the insertText
     * we send back over this prefix. This prefix sometimes differs from what we see as prefix as the namespace
     * separator (the backslash \) whilst these clients don't. Using a {@see TextEdit} rather than a simple insertText
     * ensures that the entire prefix is replaced along with the insertion.
     *
     * @param array                         $constant
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(array $constant, AutocompletionProviderContext $context): TextEdit
    {
        return new TextEdit($context->getPrefixRange(), $constant['name']);
    }
}
