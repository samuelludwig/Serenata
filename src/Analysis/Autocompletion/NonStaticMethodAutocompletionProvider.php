<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use Generator;
use AssertionError;
use UnexpectedValueException;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;
use PhpIntegrator\Analysis\CircularDependencyException;

use PhpIntegrator\Analysis\Typing\Deduction\ExpressionTypeDeducer;

use PhpIntegrator\Indexing\Structures\File;

/**
 * Provides non-static member method autocompletion suggestions at a specific location in a file.
 */
final class NonStaticMethodAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var ExpressionTypeDeducer
     */
    private $expressionTypeDeducer;

    /**
     * @var ClasslikeInfoBuilder
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
     * @param ExpressionTypeDeducer                                         $expressionTypeDeducer
     * @param ClasslikeInfoBuilder                                          $classlikeInfoBuilder
     * @param FunctionAutocompletionSuggestionLabelCreator                  $functionAutocompletionSuggestionLabelCreator
     * @param FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator
     * @param AutocompletionSuggestionTypeFormatter                         $autocompletionSuggestionTypeFormatter
     */
    public function __construct(
        ExpressionTypeDeducer $expressionTypeDeducer,
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        FunctionAutocompletionSuggestionLabelCreator $functionAutocompletionSuggestionLabelCreator,
        FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator,
        AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter
    ) {
        $this->expressionTypeDeducer = $expressionTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->functionAutocompletionSuggestionLabelCreator = $functionAutocompletionSuggestionLabelCreator;
        $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator = $functionAutocompletionSuggestionParanthesesNecessityEvaluator;
        $this->autocompletionSuggestionTypeFormatter = $autocompletionSuggestionTypeFormatter;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $types = $this->expressionTypeDeducer->deduce(
            $file,
            $code,
            $offset,
            null,
            true
        );

        $classlikeInfoElements = array_map(function (string $type) {
            try {
                return $this->classlikeInfoBuilder->getClasslikeInfo($type);
            } catch (UnexpectedValueException|CircularDependencyException $e) {
                return null;
            }
        }, $types);

        $classlikeInfoElements = array_filter($classlikeInfoElements);

        $shouldIncludeParanthesesInInsertText = $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator
            ->evaluate($code, $offset);

        foreach ($classlikeInfoElements as $classlikeInfoElement) {
            yield from $this->createSuggestionsForClasslikeInfo(
                $classlikeInfoElement,
                $shouldIncludeParanthesesInInsertText
            );
        }
    }

    /**
     * @param array $classlikeInfo
     * @param bool  $shouldIncludeParanthesesInInsertText
     *
     * @return Generator
     */
    private function createSuggestionsForClasslikeInfo(
        array $classlikeInfo,
        bool $shouldIncludeParanthesesInInsertText
    ): Generator {
        foreach ($classlikeInfo['methods'] as $method) {
            if (!$method['isStatic']) {
                yield $this->createSuggestion($method, $shouldIncludeParanthesesInInsertText);
            }
        }
    }

    /**
     * @param array $method
     * @param bool  $shouldIncludeParanthesesInInsertText
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $method,
        bool $shouldIncludeParanthesesInInsertText
    ): AutocompletionSuggestion {
        $insertText = $method['name'];
        $placeCursorBetweenParentheses = !empty($method['parameters']);

        if ($shouldIncludeParanthesesInInsertText) {
            $insertText .= '()';
        }

        return new AutocompletionSuggestion(
            $method['name'],
            SuggestionKind::METHOD,
            $insertText,
            null,
            $this->functionAutocompletionSuggestionLabelCreator->create($method),
            $method['shortDescription'],
            [
                'isDeprecated'                  => $method['isDeprecated'],
                'declaringStructure'            => $method['declaringStructure'],
                'returnTypes'                   => $this->autocompletionSuggestionTypeFormatter->format($method['returnTypes']),
                'protectionLevel'               => $this->extractProtectionLevelStringFromMemberData($method),
                'placeCursorBetweenParentheses' => $placeCursorBetweenParentheses
            ]
        );
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

        throw new AssertionError('Unknown protection level encountered');
    }
}
