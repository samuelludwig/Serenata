<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Analysis\NamespaceListProviderInterface;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Utility\TextEdit;

/**
 * Provides namespace autocompletion suggestions at a specific location in a file.
 */
final class NamespaceAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var NamespaceListProviderInterface
     */
    private $namespaceListProvider;

    /**
     * @var BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param NamespaceListProviderInterface             $namespaceListProvider
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param int                                        $resultLimit
     */
    public function __construct(
        NamespaceListProviderInterface $namespaceListProvider,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        int $resultLimit
    ) {
        $this->namespaceListProvider = $namespaceListProvider;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        $existingNames = [];

        $namespaceArrays = array_filter(
            $this->namespaceListProvider->getAll(),
            function (array $namespace) use (&$existingNames): bool {
                if ($namespace['name'] === null) {
                    return false;
                } elseif (isset($existingNames[$namespace['name']])) {
                    return false;
                }

                $existingNames[$namespace['name']] = true;

                return true;
            }
        );

        /** @var array $bestApproximations */
        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $namespaceArrays,
            $context->getPrefix(),
            'name',
            $this->resultLimit
        );

        foreach ($bestApproximations as $namespace) {
            yield $this->createSuggestion($namespace, $context);
        }
    }

    /**
     * @param array                         $namespace
     * @param AutocompletionProviderContext $context
     *
     * @return CompletionItem
     */
    private function createSuggestion(
        array $namespace,
        AutocompletionProviderContext $context
    ): CompletionItem {
        $fqcnWithoutLeadingSlash = $namespace['name'];

        if ($fqcnWithoutLeadingSlash[0] === '\\') {
            $fqcnWithoutLeadingSlash = mb_substr($fqcnWithoutLeadingSlash, 1);
        }

        return new CompletionItem(
            $fqcnWithoutLeadingSlash,
            CompletionItemKind::MODULE,
            $namespace['name'],
            $this->getTextEditForSuggestion($namespace, $context),
            $fqcnWithoutLeadingSlash,
            null,
            [],
            false,
            'namespace'
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
     * @param array                         $namespace
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(array $namespace, AutocompletionProviderContext $context): TextEdit
    {
        return new TextEdit(
            $context->getPrefixRange(),
            str_replace('\\', '\\\\', $namespace['name'])
        );
    }
}
