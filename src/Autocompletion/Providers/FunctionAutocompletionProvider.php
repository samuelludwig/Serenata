<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;
use Serenata\Autocompletion\FunctionParametersEvaluator;
use Serenata\Autocompletion\CompletionItemDetailFormatter;
use Serenata\Autocompletion\FunctionAutocompletionSuggestionLabelCreator;
use Serenata\Autocompletion\FunctionAutocompletionSuggestionParanthesesNecessityEvaluator;

use Serenata\Utility\TextEdit;

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
     * @var BestStringApproximationDeterminerInterface
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
     * @var CompletionItemDetailFormatter
     */
    private $completionItemDetailFormatter;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @var FunctionParametersEvaluator
     */
    private $functionParametersEvaluator;

    /**
     * @param FunctionListProviderInterface                                 $functionListProvider
     * @param FunctionParametersEvaluator                                   $functionParametersEvaluator
     * @param BestStringApproximationDeterminerInterface                    $bestStringApproximationDeterminer
     * @param FunctionAutocompletionSuggestionLabelCreator                  $functionAutocompletionSuggestionLabelCreator
     * @param FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator
     * @param CompletionItemDetailFormatter                                 $completionItemDetailFormatter
     * @param int                                                           $resultLimit
     */
    public function __construct(
        FunctionListProviderInterface $functionListProvider,
        FunctionParametersEvaluator $functionParametersEvaluator,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        FunctionAutocompletionSuggestionLabelCreator $functionAutocompletionSuggestionLabelCreator,
        FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator,
        CompletionItemDetailFormatter $completionItemDetailFormatter,
        int $resultLimit
    ) {
        $this->functionListProvider = $functionListProvider;
        $this->functionParametersEvaluator = $functionParametersEvaluator;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->functionAutocompletionSuggestionLabelCreator = $functionAutocompletionSuggestionLabelCreator;
        $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator = $functionAutocompletionSuggestionParanthesesNecessityEvaluator;
        $this->completionItemDetailFormatter = $completionItemDetailFormatter;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        $shouldIncludeParanthesesInInsertText = $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator
            ->evaluate($context->getTextDocumentItem(), $context->getPosition());

        /** @var array[] $bestApproximations */
        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $this->functionListProvider->getAll(),
            $context->getPrefix(),
            'name',
            $this->resultLimit
        );

        foreach ($bestApproximations as $function) {
            yield $this->createSuggestion($function, $context, $shouldIncludeParanthesesInInsertText);
        }
    }

    /**
     * @param array                         $function
     * @param AutocompletionProviderContext $context
     * @param bool                          $shouldIncludeParanthesesInInsertText
     *
     * @return CompletionItem
     */
    private function createSuggestion(
        array $function,
        AutocompletionProviderContext $context,
        bool $shouldIncludeParanthesesInInsertText
    ): CompletionItem {
        return new CompletionItem(
            $function['fqcn'],
            CompletionItemKind::FUNCTION,
            $this->getInsertTextForSuggestion($function, $shouldIncludeParanthesesInInsertText),
            $this->getTextEditForSuggestion($function, $context, $shouldIncludeParanthesesInInsertText),
            $this->functionAutocompletionSuggestionLabelCreator->create($function),
            $function['shortDescription'],
            [],
            $function['isDeprecated'],
            $this->completionItemDetailFormatter->format(null, null, $function['returnTypes'])
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
     * @param array                         $function
     * @param AutocompletionProviderContext $context
     * @param bool                          $shouldIncludeParanthesesInInsertText
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(
        array $function,
        AutocompletionProviderContext $context,
        bool $shouldIncludeParanthesesInInsertText
    ): TextEdit {
        return new TextEdit(
            $context->getPrefixRange(),
            $this->getInsertTextForSuggestion($function, $shouldIncludeParanthesesInInsertText)
        );
    }

    /**
     * @param array $function
     * @param bool  $shouldIncludeParanthesesInInsertText
     *
     * @return string
     */
    private function getInsertTextForSuggestion(array $function, bool $shouldIncludeParanthesesInInsertText): string
    {
        $insertText = $function['name'];

        if ($shouldIncludeParanthesesInInsertText) {
            if ($this->functionParametersEvaluator->hasRequiredParameters($function)) {
                $insertText .= '($0)';
            } else {
                $insertText .= '()$0';
            }
        }

        return $insertText;
    }
}
