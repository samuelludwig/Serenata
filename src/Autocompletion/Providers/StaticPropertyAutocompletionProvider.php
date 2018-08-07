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
use Serenata\Autocompletion\AutocompletionSuggestionTypeFormatter;


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

        foreach ($classlikeInfoElements as $classlikeInfoElement) {
            yield from $this->createSuggestionsForClasslikeInfo($classlikeInfoElement, $context->getPrefix());
        }
    }

    /**
     * @param array  $classlikeInfo
     * @param string $prefix
     *
     * @return Generator
     */
    private function createSuggestionsForClasslikeInfo(array $classlikeInfo, string $prefix): Generator
    {
        foreach ($classlikeInfo['properties'] as $property) {
            if ($property['isStatic']) {
                yield $this->createSuggestion($property, $prefix);
            }
        }
    }

    /**
     * @param array  $property
     * @param string $prefix
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $property, string $prefix): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            '$' . $property['name'],
            SuggestionKind::PROPERTY,
            '$' . $property['name'],
            null,
            $property['name'],
            $property['shortDescription'],
            [
                // TODO: Deprecated, replaced with "detail". Remove in the next major version.
                'declaringStructure' => $property['declaringStructure'],
                'returnTypes'        => $this->autocompletionSuggestionTypeFormatter->format($property['types']),
                'protectionLevel'    => $this->extractProtectionLevelStringFromMemberData($property),
                'prefix'             => $prefix,
            ],
            [],
            $property['isDeprecated'],
            array_slice(explode('\\', $property['declaringStructure']['fqcn']), -1)[0]
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
