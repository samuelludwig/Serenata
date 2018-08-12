<?php

namespace Serenata\Autocompletion\Providers;

use Generator;
use LogicException;
use UnexpectedValueException;

use Serenata\Analysis\CircularDependencyException;
use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\Deduction\ExpressionTypeDeducer;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\FunctionParametersEvaluator;
use Serenata\Autocompletion\AutocompletionSuggestionTypeFormatter;
use Serenata\Autocompletion\FunctionAutocompletionSuggestionLabelCreator;
use Serenata\Autocompletion\FunctionAutocompletionSuggestionParanthesesNecessityEvaluator;

use Serenata\Utility\TextEdit;

/**
 * Provides static member method autocompletion suggestions at a specific location in a file.
 */
final class StaticMethodAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var ExpressionTypeDeducer
     */
    private $expressionTypeDeducer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

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
     * @var FunctionParametersEvaluator
     */
    private $functionParametersEvaluator;

    /**
     * @param ExpressionTypeDeducer                                         $expressionTypeDeducer
     * @param ClasslikeInfoBuilderInterface                                 $classlikeInfoBuilder
     * @param FunctionParametersEvaluator                                   $functionParametersEvaluator
     * @param FunctionAutocompletionSuggestionLabelCreator                  $functionAutocompletionSuggestionLabelCreator
     * @param FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator
     * @param AutocompletionSuggestionTypeFormatter                         $autocompletionSuggestionTypeFormatter
     */
    public function __construct(
        ExpressionTypeDeducer $expressionTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        FunctionParametersEvaluator $functionParametersEvaluator,
        FunctionAutocompletionSuggestionLabelCreator $functionAutocompletionSuggestionLabelCreator,
        FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator,
        AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter
    ) {
        $this->expressionTypeDeducer = $expressionTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->functionParametersEvaluator = $functionParametersEvaluator;
        $this->functionAutocompletionSuggestionLabelCreator = $functionAutocompletionSuggestionLabelCreator;
        $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator = $functionAutocompletionSuggestionParanthesesNecessityEvaluator;
        $this->autocompletionSuggestionTypeFormatter = $autocompletionSuggestionTypeFormatter;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        $types = $this->expressionTypeDeducer->deduce(
            $context->getTextDocumentItem(),
            $context->getPosition(),
            null,
            true
        );

        $classlikeInfoElements = array_map(function (string $type) {
            try {
                return $this->classlikeInfoBuilder->build($type);
            } catch (UnexpectedValueException|CircularDependencyException $e) {
                return null;
            }
        }, $types);

        $classlikeInfoElements = array_filter($classlikeInfoElements);

        $shouldIncludeParanthesesInInsertText = $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator
            ->evaluate($context->getTextDocumentItem(), $context->getPosition());

        foreach ($classlikeInfoElements as $classlikeInfoElement) {
            yield from $this->createSuggestionsForClasslikeInfo(
                $classlikeInfoElement,
                $context,
                $shouldIncludeParanthesesInInsertText
            );
        }
    }

    /**
     * @param array                         $classlikeInfo
     * @param AutocompletionProviderContext $context
     * @param bool                          $shouldIncludeParanthesesInInsertText
     *
     * @return Generator
     */
    private function createSuggestionsForClasslikeInfo(
        array $classlikeInfo,
        AutocompletionProviderContext $context,
        bool $shouldIncludeParanthesesInInsertText
    ): Generator {
        foreach ($classlikeInfo['methods'] as $method) {
            if ($method['isStatic']) {
                yield $this->createSuggestion($method, $context, $shouldIncludeParanthesesInInsertText);
            }
        }
    }

    /**
     * @param array                         $method
     * @param AutocompletionProviderContext $context
     * @param bool                          $shouldIncludeParanthesesInInsertText
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $method,
        AutocompletionProviderContext $context,
        bool $shouldIncludeParanthesesInInsertText
    ): AutocompletionSuggestion {
        return new AutocompletionSuggestion(
            $method['name'],
            SuggestionKind::METHOD,
            $this->getInsertTextForSuggestion($method, $shouldIncludeParanthesesInInsertText),
            $this->getTextEditForSuggestion($method, $context, $shouldIncludeParanthesesInInsertText),
            $this->functionAutocompletionSuggestionLabelCreator->create($method),
            $method['shortDescription'],
            [
                // TODO: Deprecated, replace with "detail". Remove in the next major version.
                'returnTypes'        => $this->autocompletionSuggestionTypeFormatter->format($method['returnTypes']),
                'protectionLevel'    => $this->extractProtectionLevelStringFromMemberData($method),
            ],
            [],
            $method['isDeprecated'],
            array_slice(explode('\\', $method['declaringStructure']['fqcn']), -1)[0]
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
     * @param array                         $method
     * @param AutocompletionProviderContext $context
     * @param bool                          $shouldIncludeParanthesesInInsertText
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(
        array $method,
        AutocompletionProviderContext $context,
        bool $shouldIncludeParanthesesInInsertText
    ): TextEdit {
        return new TextEdit(
            $context->getPrefixRange(),
            $this->getInsertTextForSuggestion($method, $shouldIncludeParanthesesInInsertText)
        );
    }

    /**
     * @param array $method
     * @param bool  $shouldIncludeParanthesesInInsertText
     *
     * @return string
     */
    private function getInsertTextForSuggestion(array $method, bool $shouldIncludeParanthesesInInsertText): string
    {
        $insertText = $method['name'];

        if ($shouldIncludeParanthesesInInsertText) {
            if ($this->functionParametersEvaluator->hasRequiredParameters($method)) {
                $insertText .= '($0)';
            } else {
                $insertText .= '()$0';
            }
        }

        return $insertText;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function extractProtectionLevelStringFromMemberData(array $data): string
    {
        if ($data['isPublic']) {
            return 'public';
        } elseif ($data['isProtected']) {
            return 'protected';
        } elseif ($data['isPrivate']) {
            return 'private';
        }

        throw new LogicException('Unknown protection level encountered');
    }
}
