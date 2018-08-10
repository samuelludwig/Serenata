<?php

namespace Serenata\Autocompletion\Providers;

use PhpParser\Node;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;
use Serenata\Analysis\ClasslikeListProviderInterface;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Common\Position;

use Serenata\Indexing\Structures\ClasslikeTypeNameValue;

use Serenata\Refactoring\UseStatementInsertionCreator;
use Serenata\Refactoring\UseStatementInsertionCreationException;

use Serenata\Utility\TextEdit;
use Serenata\Utility\NodeHelpers;

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
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param NodeAtOffsetLocatorInterface               $nodeAtOffsetLocator
     * @param int                                        $resultLimit
     */
    public function __construct(
        ClasslikeListProviderInterface $classlikeListProvider,
        UseStatementInsertionCreator $useStatementInsertionCreator,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        int $resultLimit
    ) {
        $this->classlikeListProvider = $classlikeListProvider;
        $this->useStatementInsertionCreator = $useStatementInsertionCreator;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        /** @var array[] $bestApproximations */
        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $this->classlikeListProvider->getAll(),
            $context->getPrefix(),
            'fqcn',
            $this->resultLimit
        );

        foreach ($bestApproximations as $classlike) {
            yield $this->createSuggestion($classlike, $context);
        }
    }

    /**
     * @param array                         $classlike
     * @param AutocompletionProviderContext $context
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $classlike,
        AutocompletionProviderContext $context
    ): AutocompletionSuggestion {
        return new AutocompletionSuggestion(
            $classlike['fqcn'],
            $classlike['type'] === ClasslikeTypeNameValue::TRAIT_ ? SuggestionKind::MIXIN : SuggestionKind::CLASS_,
            $this->getInsertTextForSuggestion($classlike, $context),
            $this->getTextEditForSuggestion($classlike, $context),
            $this->getFqcnWithoutLeadingSlash($classlike),
            $classlike['shortDescription'],
            [
                'returnTypes'  => $classlike['type'],
            ],
            $this->createAdditionalTextEditsForSuggestion($classlike, $context),
            $classlike['isDeprecated']
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
     * @param array                         $classlike
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(array $classlike, AutocompletionProviderContext $context): TextEdit
    {
        return new TextEdit($context->getPrefixRange(), $this->getInsertTextForSuggestion($classlike, $context));
    }

    /**
     * @param array                         $classlike
     * @param AutocompletionProviderContext $context
     *
     * @return string
     */
    private function getInsertTextForSuggestion(array $classlike, AutocompletionProviderContext $context): string
    {
        if ($context->getPrefix() !== '' && $context->getPrefix()[0] === '\\') {
            return $classlike['fqcn'];
        } elseif ($this->isInsideUseStatement($context)) {
            return mb_substr($classlike['fqcn'], 1);
        }

        // We try to add an import that has only as many parts of the namespace as needed, for example, if the user
        // types 'Foo\Class' and confirms the suggestion 'My\Foo\Class', we add an import for 'My\Foo' and leave the
        // user's code at 'Foo\Class' as a relative import. We only add the full 'My\Foo\Class' if the user were to
        // type just 'Class' and then select 'My\Foo\Class' (i.e. we remove as many segments from the suggestion
        // as the user already has in his code).
        $partsToSlice = (count(explode('\\', $context->getPrefix())) - 1);
        $parts = explode('\\', $this->getFqcnWithoutLeadingSlash($classlike));

        // Don't try to add use statements for class names that the user wants to make absolute by adding a leading
        // slash.
        return implode('\\', array_slice($parts, -$partsToSlice - 1));
    }

    /**
     * @param array                         $classlike
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit[]
     */
    private function createAdditionalTextEditsForSuggestion(array $classlike, AutocompletionProviderContext $context): array
    {
        if ($context->getPrefix() !== '' && $context->getPrefix()[0] === '\\') {
            return [];
        } elseif ($this->isInsideUseStatement($context)) {
            return [];
        }

        $partsToSlice = (count(explode('\\', $context->getPrefix())) - 1);
        $parts = explode('\\', $this->getFqcnWithoutLeadingSlash($classlike));
        $nameToImport = implode('\\', array_slice($parts, 0, count($parts) - $partsToSlice));

        try {
            return [$this->useStatementInsertionCreator->create(
                $nameToImport,
                UseStatementKind::TYPE_CLASSLIKE,
                $context->getTextDocumentItem(),
                $context->getPosition(),
                true
            )];
        } catch (UseStatementInsertionCreationException $e) {
            return [];
        }
    }

    /**
     * @param AutocompletionProviderContext $context
     *
     * @return bool
     */
    private function isInsideUseStatement(AutocompletionProviderContext $context): bool
    {
        // The position the position is at may already be the start of another node. We're interested in what's just
        // before the position (usually the cursor), not what is "at" or "just to the right" of the cursor, hence the
        // -1.
        $position = new Position(
            $context->getPosition()->getLine(),
            max($context->getPosition()->getCharacter() - 1, 0)
        );

        $nodeAtOffset = $this->nodeAtOffsetLocator->locate($context->getTextDocumentItem(), $position);

        if ($nodeAtOffset->getNode() === null) {
            return false;
        }

        return NodeHelpers::findAncestorOfAnyType($nodeAtOffset->getNode(), Node\Stmt\Use_::class) !== null;
    }
}
