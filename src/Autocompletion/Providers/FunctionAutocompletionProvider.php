<?php

namespace Serenata\Autocompletion\Providers;

use PhpParser\Node;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;
use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

use Serenata\Autocompletion\CompletionItem;
use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\FunctionParametersEvaluator;
use Serenata\Autocompletion\CompletionItemDetailFormatter;
use Serenata\Autocompletion\FunctionAutocompletionSuggestionLabelCreator;
use Serenata\Autocompletion\FunctionAutocompletionSuggestionParanthesesNecessityEvaluator;

use Serenata\Common\Position;

use Serenata\Refactoring\UseStatementInsertionCreator;
use Serenata\Refactoring\UseStatementInsertionCreationException;

use Serenata\Utility\TextEdit;
use Serenata\Utility\NodeHelpers;

/**
 * Provides function autocompletion suggestions at a specific location in a file.
 */
final class FunctionAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @var UseStatementInsertionCreator
     */
    private $useStatementInsertionCreator;

    /**
     * @var FunctionParametersEvaluator
     */
    private $functionParametersEvaluator;

    /**
     * @var BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var FunctionAutocompletionSuggestionLabelCreator
     */
    private $functionAutocompletionSuggestionLabelCreator;

    /**
     * @var FunctionAutocompletionSuggestionParanthesesNecessityEvaluator
     */
    private $functionAutocompletionSuggestionParanthesesNecessityEvaluator;

    /**
     * @var CompletionItemDetailFormatter
     */
    private $completionItemDetailFormatter;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param FunctionListProviderInterface                                 $functionListProvider
     * @param UseStatementInsertionCreator                                  $useStatementInsertionCreator
     * @param FunctionParametersEvaluator                                   $functionParametersEvaluator
     * @param BestStringApproximationDeterminerInterface                    $bestStringApproximationDeterminer
     * @param NodeAtOffsetLocatorInterface                                  $nodeAtOffsetLocator
     * @param FunctionAutocompletionSuggestionLabelCreator                  $functionAutocompletionSuggestionLabelCreator
     * @param FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator
     * @param CompletionItemDetailFormatter                                 $completionItemDetailFormatter
     * @param int                                                           $resultLimit
     */
    public function __construct(
        FunctionListProviderInterface $functionListProvider,
        UseStatementInsertionCreator $useStatementInsertionCreator,
        FunctionParametersEvaluator $functionParametersEvaluator,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        FunctionAutocompletionSuggestionLabelCreator $functionAutocompletionSuggestionLabelCreator,
        FunctionAutocompletionSuggestionParanthesesNecessityEvaluator $functionAutocompletionSuggestionParanthesesNecessityEvaluator,
        CompletionItemDetailFormatter $completionItemDetailFormatter,
        int $resultLimit
    ) {
        $this->functionListProvider = $functionListProvider;
        $this->useStatementInsertionCreator = $useStatementInsertionCreator;
        $this->functionParametersEvaluator = $functionParametersEvaluator;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->functionAutocompletionSuggestionLabelCreator = $functionAutocompletionSuggestionLabelCreator;
        $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator = $functionAutocompletionSuggestionParanthesesNecessityEvaluator;
        $this->completionItemDetailFormatter = $completionItemDetailFormatter;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        $shouldIncludeParanthesesInInsertText = $this->functionAutocompletionSuggestionParanthesesNecessityEvaluator
            ->evaluate($context->getTextDocumentItem(), $context->getPosition());

        /** @var array[] $bestApproximations */
        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $this->functionListProvider->getAll(),
            $context->getPrefix(),
            'fqcn',
            $this->resultLimit
        );

        foreach ($bestApproximations as $function) {
            yield $this->createSuggestion($function, $context, $shouldIncludeParanthesesInInsertText);
        }
    }

    /**
     * @param array                         $function
     * @param AutocompletionProviderContext $context
     * @param bool                          $shouldIncludeParanthesesInInsertText
     *
     * @return CompletionItem
     */
    private function createSuggestion(
        array $function,
        AutocompletionProviderContext $context,
        bool $shouldIncludeParanthesesInInsertText
    ): CompletionItem {
        return new CompletionItem(
            $function['fqcn'],
            CompletionItemKind::FUNCTION,
            $this->getInsertTextForSuggestion($function, $context, $shouldIncludeParanthesesInInsertText),
            $this->getTextEditForSuggestion($function, $context, $shouldIncludeParanthesesInInsertText),
            $this->functionAutocompletionSuggestionLabelCreator->create($function),
            $function['shortDescription'],
            $this->createAdditionalTextEditsForSuggestion($function, $context),
            $function['isDeprecated'],
            $this->completionItemDetailFormatter->format(null, null, $function['returnTypes'])
        );
    }

    /**
     * @param array $function
     *
     * @return string
     */
    private function getFqcnWithoutLeadingSlash(array $function): string
    {
        $fqcn = $function['fqcn'];

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
     * @param array                         $function
     * @param AutocompletionProviderContext $context
     * @param bool                          $shouldIncludeParanthesesInInsertText
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(
        array $function,
        AutocompletionProviderContext $context,
        bool $shouldIncludeParanthesesInInsertText
    ): TextEdit {
        return new TextEdit(
            $context->getPrefixRange(),
            $this->getInsertTextForSuggestion($function, $context, $shouldIncludeParanthesesInInsertText)
        );
    }

    /**
     * @param array                         $function
     * @param AutocompletionProviderContext $context
     * @param bool                          $shouldIncludeParanthesesInInsertText
     *
     * @return string
     */
    private function getInsertTextForSuggestion(
        array $function,
        AutocompletionProviderContext $context,
        bool $shouldIncludeParanthesesInInsertText
    ): string {
        $paranthesesAreAllowed = true;
        $insertText = $function['name'];

        if ($context->getPrefix() !== '' && $context->getPrefix()[0] === '\\') {
            $insertText = $function['fqcn'];
        } elseif ($this->isInsideUseStatement($context)) {
            $insertText = mb_substr($function['fqcn'], 1);
            $paranthesesAreAllowed = false;
        } else {
            // We try to add an import that has only as many parts of the namespace as needed, for example, if the user
            // types 'Foo\Class' and confirms the suggestion 'My\Foo\Class', we add an import for 'My\Foo' and leave the
            // user's code at 'Foo\Class' as a relative import. We only add the full 'My\Foo\Class' if the user were to
            // type just 'Class' and then select 'My\Foo\Class' (i.e. we remove as many segments from the suggestion
            // as the user already has in his code).
            $partsToSlice = (count(explode('\\', $context->getPrefix())) - 1);
            $parts = explode('\\', $this->getFqcnWithoutLeadingSlash($function));

            // Don't try to add use statements for class names that the user wants to make absolute by adding a leading
            // slash.
            $insertText = implode('\\', array_slice($parts, -$partsToSlice - 1));
        }

        if ($shouldIncludeParanthesesInInsertText && $paranthesesAreAllowed) {
            if ($this->functionParametersEvaluator->hasRequiredParameters($function)) {
                $insertText .= '($0)';
            } else {
                $insertText .= '()$0';
            }
        }

        return $insertText;
    }

    /**
     * @param array                         $function
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit[]
     */
    private function createAdditionalTextEditsForSuggestion(
        array $function,
        AutocompletionProviderContext $context
    ): array {
        if ($context->getPrefix() !== '' && $context->getPrefix()[0] === '\\') {
            return [];
        } elseif ($this->isInsideUseStatement($context)) {
            return [];
        }

        $partsToSlice = (count(explode('\\', $context->getPrefix())) - 1);
        $parts = explode('\\', $this->getFqcnWithoutLeadingSlash($function));
        $nameToImport = implode('\\', array_slice($parts, 0, count($parts) - $partsToSlice));

        if (count($parts) === 1) {
            // Don't generate anything for global, unqualified functions. It won't break anything but there is currently
            // little use for it apart from micro-optimization purposes, which we currently don't support.
            return [];
        }

        try {
            return [$this->useStatementInsertionCreator->create(
                $nameToImport,
                $partsToSlice === 0 ? UseStatementKind::TYPE_FUNCTION : UseStatementKind::TYPE_CLASSLIKE,
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
