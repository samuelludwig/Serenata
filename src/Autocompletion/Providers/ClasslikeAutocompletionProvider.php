<?php

namespace Serenata\Autocompletion\Providers;

use PhpParser\Node;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\NodeHelpers;
use Serenata\Utility\SourceCodeHelpers;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;
use Serenata\Analysis\ClasslikeListProviderInterface;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\AutocompletionPrefixDeterminerInterface;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Indexing\Structures\File;
use Serenata\Indexing\Structures\ClasslikeTypeNameValue;

use Serenata\Refactoring\UseStatementInsertionCreator;
use Serenata\Refactoring\UseStatementInsertionCreationException;

use Serenata\Utility\TextEdit;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

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
     * @var UseStatementInsertionCreator
     */
    private $useStatementInsertionCreator;

    /**
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @var BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param ClasslikeListProviderInterface             $classlikeListProvider
     * @param UseStatementInsertionCreator               $useStatementInsertionCreator
     * @param AutocompletionPrefixDeterminerInterface    $autocompletionPrefixDeterminer
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param NodeAtOffsetLocatorInterface               $nodeAtOffsetLocator
     * @param int                                        $resultLimit
     */
    public function __construct(
        ClasslikeListProviderInterface $classlikeListProvider,
        UseStatementInsertionCreator $useStatementInsertionCreator,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        int $resultLimit
    ) {
        $this->classlikeListProvider = $classlikeListProvider;
        $this->useStatementInsertionCreator = $useStatementInsertionCreator;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $this->classlikeListProvider->getAll(),
            $prefix,
            'fqcn',
            $this->resultLimit
        );

        foreach ($bestApproximations as $classlike) {
            yield $this->createSuggestion($classlike, $code, $offset, $prefix);
        }
    }

    /**
     * @param array  $classlike
     * @param string $code
     * @param int    $offset
     * @param string $prefix
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $classlike,
        string $code,
        int $offset,
        string $prefix
    ): AutocompletionSuggestion {
        return new AutocompletionSuggestion(
            $classlike['fqcn'],
            $classlike['type'] === ClasslikeTypeNameValue::TRAIT_ ? SuggestionKind::MIXIN : SuggestionKind::CLASS_,
            $this->getInsertTextForSuggestion($classlike, $code, $offset),
            $this->getTextEditForSuggestion($classlike, $code, $offset, $prefix),
            $this->getFqcnWithoutLeadingSlash($classlike),
            $classlike['shortDescription'],
            [
                'isDeprecated' => $classlike['isDeprecated'],
                'returnTypes'  => $classlike['type'],
                'prefix'       => $prefix
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
     * Generate a {@see TextEdit} for the suggestion.
     *
     * Some clients automatically determine the prefix to replace on their end (e.g. Atom) and just paste the insertText
     * we send back over this prefix. This prefix sometimes differs from what we see as prefix as the namespace
     * separator (the backslash \) whilst these clients don't. Using a {@see TextEdit} rather than a simple insertText
     * ensures that the entire prefix is replaced along with the insertion.
     *
     * @param array  $classlike
     * @param string $code
     * @param int    $offset
     * @param string $prefix
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(array $classlike, string $code, int $offset, string $prefix): TextEdit
    {
        $line = SourceCodeHelpers::calculateLineByOffset($code, $offset) - 1;
        $character = SourceCodeHelpers::getCharacterOnLineFromByteOffset($offset, $line, $code);

        return new TextEdit(
            new Range(new Position($line, $character - mb_strlen($prefix)), new Position($line, $character)),
            $this->getInsertTextForSuggestion($classlike, $code, $offset)
        );
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
        } elseif ($this->isInsideUseStatement($code, $offset)) {
            return mb_substr($classlike['fqcn'], 1);
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
        } elseif ($this->isInsideUseStatement($code, $offset)) {
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
    }

    /**
     * @param string $code
     * @param int    $offset
     *
     * @return bool
     */
    private function isInsideUseStatement(string $code, int $offset): bool
    {
        // The position the position is at may already be the start of another node. We're interested in what's just
        // before the position (usually the cursor), not what is "at" or "just to the right" of the cursor, hence the
        // -1.
        $nodeAtOffset = $this->nodeAtOffsetLocator->locate($code, $offset - 1);

        if ($nodeAtOffset->getNode() === null) {
            return false;
        }

        return NodeHelpers::findAncestorOfAnyType($nodeAtOffset->getNode(), Node\Stmt\Use_::class) !== null;
    }
}
