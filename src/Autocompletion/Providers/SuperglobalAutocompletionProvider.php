<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;



use Serenata\Utility\TextEdit;

/**
 * Provides superglobal autocompletion suggestions at a specific location in a file.
 *
 * @see https://secure.php.net/manual/en/reserved.keywords.php
 * @see https://secure.php.net/manual/en/reserved.other-reserved-words.php
 */
final class SuperglobalAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        foreach ($this->getSuperGlobals() as $superGlobal) {
            yield $this->createSuggestion($superGlobal, $context);
        }
    }

    /**
     * @param array                         $superGlobal
     * @param AutocompletionProviderContext $context
     *
     * @return CompletionItem
     */
    private function createSuggestion(
        array $superGlobal,
        AutocompletionProviderContext $context
    ): CompletionItem {
        return new CompletionItem(
            $superGlobal['name'],
            CompletionItemKind::VARIABLE,
            $superGlobal['name'],
            $this->getTextEditForSuggestion($superGlobal, $context),
            $superGlobal['name'],
            'PHP superglobal',
            [],
            false,
            $superGlobal['type']
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
     * @param array                         $superGlobal
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(array $superGlobal, AutocompletionProviderContext $context): TextEdit
    {
        return new TextEdit($context->getPrefixRange(), str_replace('$', '\$', $superGlobal['name']));
    }

    /**
     * @return array
     */
    private function getSuperGlobals(): array
    {
        return [
            ['name' => '$argc',    'type' => 'int'],
            ['name' => '$argv',    'type' => 'array'],
            ['name' => '$GLOBALS', 'type' => 'array'],
            ['name' => '$_SERVER', 'type' => 'array'],
            ['name' => '$_GET',    'type' => 'array'],
            ['name' => '$_POST',   'type' => 'array'],
            ['name' => '$_FILES',  'type' => 'array'],
            ['name' => '$_COOKIE', 'type' => 'array'],
            ['name' => '$_SESSION','type' => 'array'],
            ['name' => '$_REQUEST','type' => 'array'],
            ['name' => '$_ENV',    'type' => 'array'],
        ];
    }
}
