<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Analysis\ClasslikeListProviderInterface;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Indexing\Structures\File;
use PhpIntegrator\Indexing\Structures\ClasslikeTypeNameValue;

use PhpIntegrator\Refactoring\UseStatementInsertionCreator;
use PhpIntegrator\Refactoring\UseStatementInsertionCreationException;

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
        $fqcnWithoutLeadingSlash = $classlike['fqcn'];

        if ($fqcnWithoutLeadingSlash[0] === '\\') {
            $fqcnWithoutLeadingSlash = mb_substr($fqcnWithoutLeadingSlash, 1);
        }

        $additionalTextEdits = [];
        $insertText = $fqcnWithoutLeadingSlash;

        $prefixHasLeadingSlash = false;
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        if ($prefix !== '' && $prefix[0] == '\\') {
            $prefixHasLeadingSlash = true;
            $prefix = mb_substr($prefix, 1);
        }

        $prefixParts = explode('\\', $prefix);
        $partsToSlice = (count($prefixParts) - 1);

        // We try to add an import that has only as many parts of the namespace as needed, for example, if the user
        // types 'Foo\Class' and confirms the suggestion 'My\Foo\Class', we add an import for 'My\Foo' and leave the
        // user's code at 'Foo\Class' as a relative import. We only add the full 'My\Foo\Class' if the user were to
        // type just 'Class' and then select 'My\Foo\Class' (i.e. we remove as many segments from the suggestion
        // as the user already has in his code).
        if ($prefixHasLeadingSlash) {
            $insertText = '\\' . $fqcnWithoutLeadingSlash;
        } else {
            // Don't try to add use statements for class names that the user wants to make absolute by adding a leading
            // slash.
            $insertText = $this->getNameToInsert($fqcnWithoutLeadingSlash, $partsToSlice);
            $nameToImport = $this->getNameToImportViaUseStatement($fqcnWithoutLeadingSlash, $partsToSlice);

            try {
                $additionalTextEdits[] = $this->useStatementInsertionCreator->create(
                    $nameToImport,
                    UseStatementKind::TYPE_CLASSLIKE,
                    $code,
                    $offset,
                    true
                );
            } catch (UseStatementInsertionCreationException $e) {
                $additionalTextEdits = [];
            }
        }

        return new AutocompletionSuggestion(
            $fqcnWithoutLeadingSlash,
            $classlike['type'] === ClasslikeTypeNameValue::TRAIT_ ? SuggestionKind::MIXIN : SuggestionKind::CLASS_,
            $insertText,
            $fqcnWithoutLeadingSlash,
            $classlike['shortDescription'],
            [
                'isDeprecated' => $classlike['isDeprecated'],
                'returnTypes'  => $classlike['type']
            ],
            $additionalTextEdits
        );
    }

    /**
     * Returns the name to insert into the buffer.
     *
     * @param string $name                 The FQCN of the class that needs to be imported.
     * @param int    $extraPartsToMaintain The amount of parts to leave extra for the class name. For example, a value
     *                                     of 1 will return B\C instead of A\B\C. A value of 0 will return just C.
     *
     * @return string|null
     */
    private function getNameToInsert(string $name, int $extraPartsToMaintain): ?string
    {
        if ($name[0] === '\\') {
            $name = mb_substr($name, 1);
        }

        return implode('\\', array_slice(explode('\\', $name), -$extraPartsToMaintain - 1));
    }

    /**
     * Returns the name to import via a use statement.
     *
     * @param string $name          The FQCN of the class that needs to be imported.
     * @param int    $partsToPopOff The amount of parts to leave off of the end of the class name. For example, a
     *                              value of 1 will return A\B instead of A\B\C.
     *
     * @return string|null
     */
    private function getNameToImportViaUseStatement(string $name, int $partsToPopOff): ?string
    {
        if ($name[0] === '\\') {
            $name = mb_substr($name, 1);
        }

        $nameParts = explode('\\', $name);

        return implode('\\', array_slice($nameParts, 0, count($nameParts) - $partsToPopOff));
    }
}
