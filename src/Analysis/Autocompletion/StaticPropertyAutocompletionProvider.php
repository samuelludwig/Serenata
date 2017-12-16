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
 * Provides static member property autocompletion suggestions at a specific location in a file.
 */
final class StaticPropertyAutocompletionProvider implements AutocompletionProviderInterface
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
     * @param ExpressionTypeDeducer $expressionTypeDeducer
     * @param ClasslikeInfoBuilder  $classlikeInfoBuilder
     */
    public function __construct(
        ExpressionTypeDeducer $expressionTypeDeducer,
        ClasslikeInfoBuilder $classlikeInfoBuilder
    ) {
        $this->expressionTypeDeducer = $expressionTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
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
            $property['name'],
            $property['shortDescription'],
            [
                'isDeprecated'       => $property['isDeprecated'],
                'declaringStructure' => $property['declaringStructure'],
                'returnTypes'        => $this->createReturnTypes($property),
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

    /**
     * @param array $function
     *
     * @return string
     */
    private function createReturnTypes(array $function): string
    {
        $typeNames = $this->getShortReturnTypes($function);

        return implode('|', $typeNames);
    }

    /**
     * @param array $function
     *
     * @return string[]
     */
    private function getShortReturnTypes(array $function): array
    {
        $shortTypes = [];

        foreach ($function['types'] as $type) {
            $shortTypes[] = $this->getClassShortNameFromFqcn($type['fqcn']);
        }

        return $shortTypes;
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    private function getClassShortNameFromFqcn(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return array_pop($parts);
    }
}
