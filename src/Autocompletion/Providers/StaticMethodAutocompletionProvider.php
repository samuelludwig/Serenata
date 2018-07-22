<?php

namespace Serenata\Autocompletion\Providers;

use Generator;
use LogicException;
use UnexpectedValueException;

use Serenata\Analysis\CircularDependencyException;
use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\FunctionParametersEvaluator;
use Serenata\Autocompletion\AutocompletionSuggestionTypeFormatter;
use Serenata\Autocompletion\FunctionAutocompletionSuggestionLabelCreator;
use Serenata\Autocompletion\FunctionAutocompletionSuggestionParanthesesNecessityEvaluator;

use Serenata\Indexing\Structures\File;

use Serenata\Analysis\Typing\Deduction\ExpressionTypeDeducer;

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
                return $this->classlikeInfoBuilder->build($type);
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
            if ($method['isStatic']) {
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

        if ($shouldIncludeParanthesesInInsertText) {
            if ($this->functionParametersEvaluator->hasRequiredParameters($method)) {
                $insertText .= '($0)';
            } else {
                $insertText .= '()$0';
            }
        }

        return new AutocompletionSuggestion(
            $method['name'],
            SuggestionKind::METHOD,
            $insertText,
            null,
            $this->functionAutocompletionSuggestionLabelCreator->create($method),
            $method['shortDescription'],
            [
                // TODO: Deprecated, replaced with "detail". Remove in the next major version.
                'declaringStructure' => $method['declaringStructure'],
                'returnTypes'        => $this->autocompletionSuggestionTypeFormatter->format($method['returnTypes']),
                'protectionLevel'    => $this->extractProtectionLevelStringFromMemberData($method)
            ],
            [],
            $method['isDeprecated'],
            array_slice(explode('\\', $method['declaringStructure']['fqcn']), -1)[0]
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

        throw new LogicException('Unknown protection level encountered');
    }
}
