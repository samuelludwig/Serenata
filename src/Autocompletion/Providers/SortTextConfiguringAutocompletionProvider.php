<?php

namespace Serenata\Autocompletion\Providers;

use UnexpectedValueException;

use Serenata\Autocompletion\CompletionItem;

/**
 * Delegates autocompletion provision and sets the sort text on all completion items.
 *
 * Some clients want to use the "sortText" property already to sort the suggestions by. If it isn't available, they
 * will resort to using the label instead. We *don't* want this to happen as the sorting of the items we provide is
 * the correct sorting already, as it is based on fuzzy matching scores. In order to translate this into the sortText,
 * all we need to do is set incrementing numbers to have clients maintain the sorting.
 */
final class SortTextConfiguringAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var AutocompletionProviderInterface
     */
    private $delegate;

    /**
     * @param AutocompletionProviderInterface $delegate
     */
    public function __construct(AutocompletionProviderInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        /** @var CompletionItem $item */
        foreach ($this->delegate->provide($context) as $i => $item) {
            if ($item->getSortText() !== null) {
                throw new UnexpectedValueException(
                    'Sort text is expected to be empty as sorting is determined by score. Ensure your array is ' .
                    'already sorted in the appropriate order.'
                );
            }

            yield $i => new CompletionItem(
                $item->getFilterText(),
                $item->getKind(),
                $item->getInsertText(),
                $item->getTextEdit(),
                $item->getLabel(),
                $item->getDocumentation(),
                $item->getAdditionalTextEdits(),
                $item->getDeprecated(),
                $item->getDetail(),
                $i
            );
        }
    }
}
