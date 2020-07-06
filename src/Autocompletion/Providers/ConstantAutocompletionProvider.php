<?php

namespace Serenata\Autocompletion\Providers;

use PhpParser\Node;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;
use Serenata\Analysis\ConstantListProviderInterface;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;
use Serenata\Autocompletion\CompletionItemDetailFormatter;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

use Serenata\Common\Position;

use Serenata\Refactoring\UseStatementInsertionCreator;
use Serenata\Refactoring\UseStatementInsertionCreationException;

use Serenata\Utility\TextEdit;
use Serenata\Utility\NodeHelpers;

/**
 * Provides constant autocompletion suggestions at a specific location in a file.
 */
final class ConstantAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var ConstantListProviderInterface
     */
    private $constantListProvider;

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
     * @var CompletionItemDetailFormatter
     */
    private $completionItemDetailFormatter;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param ConstantListProviderInterface              $constantListProvider
     * @param UseStatementInsertionCreator               $useStatementInsertionCreator
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param NodeAtOffsetLocatorInterface               $nodeAtOffsetLocator
     * @param CompletionItemDetailFormatter              $completionItemDetailFormatter
     * @param int                                        $resultLimit
     */
    public function __construct(
        ConstantListProviderInterface $constantListProvider,
        UseStatementInsertionCreator $useStatementInsertionCreator,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        CompletionItemDetailFormatter $completionItemDetailFormatter,
        int $resultLimit
    ) {
        $this->constantListProvider = $constantListProvider;
        $this->useStatementInsertionCreator = $useStatementInsertionCreator;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->completionItemDetailFormatter = $completionItemDetailFormatter;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        /** @var array[] $bestApproximations */
        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $this->constantListProvider->getAll(),
            $context->getPrefix(),
            'fqcn',
            $this->resultLimit
        );

        foreach ($bestApproximations as $constant) {
            yield $this->createSuggestion($constant, $context);
        }
    }

    /**
     * @param array<string,mixed>           $constant
     * @param AutocompletionProviderContext $context
     *
     * @return CompletionItem
     */
    private function createSuggestion(array $constant, AutocompletionProviderContext $context): CompletionItem
    {
        return new CompletionItem(
            $constant['fqcn'],
            CompletionItemKind::CONSTANT,
            $this->getInsertTextForSuggestion($constant, $context),
            $this->getTextEditForSuggestion($constant, $context),
            $constant['name'],
            $constant['shortDescription'],
            $this->createAdditionalTextEditsForSuggestion($constant, $context),
            $constant['isDeprecated'],
            $this->completionItemDetailFormatter->format(null, null, $constant['types']) . ' — ' .
                $this->getFqcnWithoutLeadingSlash($constant)
        );
    }

    /**
     * @param array<string,mixed> $classlike
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
     * @param array<string,mixed>           $constant
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(array $constant, AutocompletionProviderContext $context): TextEdit
    {
        return new TextEdit(
            $context->getPrefixRange(),
            $this->getInsertTextForSuggestion($constant, $context)
        );
    }

    /**
     * @param array<string,mixed>           $constant
     * @param AutocompletionProviderContext $context
     *
     * @return string
     */
    private function getInsertTextForSuggestion(array $constant, AutocompletionProviderContext $context): string
    {
        $paranthesesAreAllowed = true;
        $insertText = $constant['name'];

        if ($context->getPrefix() !== '' && $context->getPrefix()[0] === '\\') {
            $insertText = $constant['fqcn'];
        } elseif ($this->isInsideUseStatement($context)) {
            $insertText = mb_substr($constant['fqcn'], 1);
            $paranthesesAreAllowed = false;
        } else {
            // We try to add an import that has only as many parts of the namespace as needed, for example, if the user
            // types 'Foo\Class' and confirms the suggestion 'My\Foo\Class', we add an import for 'My\Foo' and leave the
            // user's code at 'Foo\Class' as a relative import. We only add the full 'My\Foo\Class' if the user were to
            // type just 'Class' and then select 'My\Foo\Class' (i.e. we remove as many segments from the suggestion
            // as the user already has in his code).
            $partsToSlice = (count(explode('\\', $context->getPrefix())) - 1);
            $parts = explode('\\', $this->getFqcnWithoutLeadingSlash($constant));

            // Don't try to add use statements for class names that the user wants to make absolute by adding a leading
            // slash.
            $insertText = implode('\\', array_slice($parts, -$partsToSlice - 1));
        }

        return str_replace('\\', '\\\\', $insertText);
    }

    /**
     * @param array<string,mixed>           $constant
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit[]
     */
    private function createAdditionalTextEditsForSuggestion(
        array $constant,
        AutocompletionProviderContext $context
    ): array {
        if ($context->getPrefix() !== '' && $context->getPrefix()[0] === '\\') {
            return [];
        } elseif ($this->isInsideUseStatement($context)) {
            return [];
        }

        $partsToSlice = (count(explode('\\', $context->getPrefix())) - 1);
        $parts = explode('\\', $this->getFqcnWithoutLeadingSlash($constant));
        $nameToImport = implode('\\', array_slice($parts, 0, count($parts) - $partsToSlice));

        if (count($parts) === 1) {
            // Don't generate anything for global, unqualified functions. It won't break anything but there is currently
            // little use for it apart from micro-optimization purposes, which we currently don't support.
            return [];
        }

        try {
            return [$this->useStatementInsertionCreator->create(
                $nameToImport,
                $partsToSlice === 0 ? UseStatementKind::TYPE_CONSTANT : UseStatementKind::TYPE_CLASSLIKE,
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
