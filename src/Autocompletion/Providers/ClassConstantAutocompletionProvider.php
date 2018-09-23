<?php

namespace Serenata\Autocompletion\Providers;

use Generator;
use LogicException;
use UnexpectedValueException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\CircularDependencyException;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;
use Serenata\Autocompletion\CompletionItemDetailFormatter;

use Serenata\Analysis\Typing\Deduction\ExpressionTypeDeducer;

use Serenata\Utility\TextEdit;

/**
 * Provides member constant autocompletion suggestions at a specific location in a file.
 */
final class ClassConstantAutocompletionProvider implements AutocompletionProviderInterface
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
     * @var CompletionItemDetailFormatter
     */
    private $completionItemDetailFormatter;

    /**
     * @param ExpressionTypeDeducer                 $expressionTypeDeducer
     * @param ClasslikeInfoBuilderInterface         $classlikeInfoBuilder
     * @param CompletionItemDetailFormatter $completionItemDetailFormatter
     */
    public function __construct(
        ExpressionTypeDeducer $expressionTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        CompletionItemDetailFormatter $completionItemDetailFormatter
    ) {
        $this->expressionTypeDeducer = $expressionTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->completionItemDetailFormatter = $completionItemDetailFormatter;
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
            yield from $this->createSuggestionsForClasslikeInfo($classlikeInfoElement, $context);
        }
    }

    /**
     * @param array                         $classlikeInfo
     * @param AutocompletionProviderContext $context
     *
     * @return Generator
     */
    private function createSuggestionsForClasslikeInfo(
        array $classlikeInfo,
        AutocompletionProviderContext $context
    ): Generator {
        foreach ($classlikeInfo['constants'] as $constant) {
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
            [],
            $constant['isDeprecated'],
            $this->completionItemDetailFormatter->format(
                $constant['declaringStructure']['fqcn'],
                $this->extractProtectionLevelStringFromMemberData($constant),
                $constant['types']
            )
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
