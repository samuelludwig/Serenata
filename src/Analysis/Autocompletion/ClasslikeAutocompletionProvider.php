<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use AssertionError;

use PhpIntegrator\Analysis\ClasslikeListProviderInterface;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Indexing\Structures\File;
use PhpIntegrator\Indexing\Structures\ClasslikeTypeNameValue;

use PhpIntegrator\Refactoring\UseStatementInsertionCreator;
use PhpIntegrator\Refactoring\UseStatementInsertionCreationException;

use PhpIntegrator\Utility\TextEdit;

/**
 * Provides classlike autocompletion suggestions at a specific location in a file.
 */
final class ClasslikeAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var ClasslikeListProviderInterface
     */
    private $classlikeListProvider;

    /**
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @var UseStatementInsertionCreator
     */
    private $useStatementInsertionCreator;

    /**
     * @param ClasslikeListProviderInterface          $classlikeListProvider
     * @param AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
     * @param UseStatementInsertionCreator            $useStatementInsertionCreator
     */
    public function __construct(
        ClasslikeListProviderInterface $classlikeListProvider,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer,
        UseStatementInsertionCreator $useStatementInsertionCreator
    ) {
        $this->classlikeListProvider = $classlikeListProvider;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
        $this->useStatementInsertionCreator = $useStatementInsertionCreator;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        foreach ($this->classlikeListProvider->getAll() as $classlike) {
            yield $this->createSuggestion($classlike, $code, $offset);
        }
    }

    /**
     * @param array  $classlike
     * @param string $code
     * @param int    $offset
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $classlike, string $code, int $offset): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            $this->getFqcnWithoutLeadingSlash($classlike),
            $classlike['type'] === ClasslikeTypeNameValue::TRAIT_ ? SuggestionKind::MIXIN : SuggestionKind::CLASS_,
            $this->getInsertTextForSuggestion($classlike, $code, $offset),
            $this->getFqcnWithoutLeadingSlash($classlike),
            $classlike['shortDescription'],
            [
                'isDeprecated' => $classlike['isDeprecated'],
                'returnTypes'  => $classlike['type']
            ],
            $this->createAdditionalTextEditsForSuggestion($classlike, $code, $offset)
        );
    }

    /**
     * @param array $classlike
     *
     * @return string
     */
    private function getFqcnWithoutLeadingSlash(array $classlike): string
    {
        $fqcn = $classlike['fqcn'];

        if ($fqcn[0] === '\\') {
            return mb_substr($fqcn, 1);
        }

        return $fqcn;
    }

    /**
     * @param array  $classlike
     * @param string $code
     * @param int    $offset
     *
     * @return string
     */
    private function getInsertTextForSuggestion(array $classlike, string $code, int $offset): string
    {
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        if ($prefix !== '' && $prefix[0] === '\\') {
            return $classlike['fqcn'];
        }

        // We try to add an import that has only as many parts of the namespace as needed, for example, if the user
        // types 'Foo\Class' and confirms the suggestion 'My\Foo\Class', we add an import for 'My\Foo' and leave the
        // user's code at 'Foo\Class' as a relative import. We only add the full 'My\Foo\Class' if the user were to
        // type just 'Class' and then select 'My\Foo\Class' (i.e. we remove as many segments from the suggestion
        // as the user already has in his code).
        $partsToSlice = (count(explode('\\', $prefix)) - 1);
        $parts = explode('\\', $this->getFqcnWithoutLeadingSlash($classlike));

        // Don't try to add use statements for class names that the user wants to make absolute by adding a leading
        // slash.
        return implode('\\', array_slice($parts, -$partsToSlice - 1));
    }

    /**
     * @param array  $classlike
     * @param string $code
     * @param int    $offset
     *
     * @return TextEdit[]
     */
    private function createAdditionalTextEditsForSuggestion(array $classlike, string $code, int $offset): array
    {
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        if ($prefix !== '' && $prefix[0] === '\\') {
            return [];
        }

        $partsToSlice = (count(explode('\\', $prefix)) - 1);
        $parts = explode('\\', $this->getFqcnWithoutLeadingSlash($classlike));
        $nameToImport = implode('\\', array_slice($parts, 0, count($parts) - $partsToSlice));

        try {
            return [$this->useStatementInsertionCreator->create(
                $nameToImport,
                UseStatementKind::TYPE_CLASSLIKE,
                $code,
                $offset,
                true
            )];
        } catch (UseStatementInsertionCreationException $e) {
            return [];
        }

        throw new AssertionError('Should never be reached');
    }
}
