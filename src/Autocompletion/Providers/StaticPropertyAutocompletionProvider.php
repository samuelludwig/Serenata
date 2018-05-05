<?php

namespace Serenata\Autocompletion\Providers;

use Generator;
use AssertionError;
use UnexpectedValueException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\CircularDependencyException;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\AutocompletionSuggestionTypeFormatter;

use Serenata\Analysis\Typing\Deduction\ExpressionTypeDeducer;

use Serenata\Indexing\Structures\File;

/**
 * Provides static member property autocompletion suggestions at a specific location in a file.
 */
final class StaticPropertyAutocompletionProvider implements AutocompletionProviderInterface
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
     * @var AutocompletionSuggestionTypeFormatter
     */
    private $autocompletionSuggestionTypeFormatter;

    /**
     * @param ExpressionTypeDeducer                 $expressionTypeDeducer
     * @param ClasslikeInfoBuilderInterface         $classlikeInfoBuilder
     * @param AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter
     */
    public function __construct(
        ExpressionTypeDeducer $expressionTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter
    ) {
        $this->expressionTypeDeducer = $expressionTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
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

        foreach ($classlikeInfoElements as $classlikeInfoElement) {
            yield from $this->createSuggestionsForClasslikeInfo($classlikeInfoElement);
        }
    }

    /**
     * @param array $classlikeInfo
     *
     * @return Generator
     */
    private function createSuggestionsForClasslikeInfo(array $classlikeInfo): Generator
    {
        foreach ($classlikeInfo['properties'] as $property) {
            if ($property['isStatic']) {
                yield $this->createSuggestion($property);
            }
        }
    }

    /**
     * @param array $property
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $property): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            '$' . $property['name'],
            SuggestionKind::PROPERTY,
            '$' . $property['name'],
            null,
            $property['name'],
            $property['shortDescription'],
            [
                'isDeprecated'       => $property['isDeprecated'],
                'declaringStructure' => $property['declaringStructure'],
                'returnTypes'        => $this->autocompletionSuggestionTypeFormatter->format($property['types']),
                'protectionLevel'    => $this->extractProtectionLevelStringFromMemberData($property)
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
