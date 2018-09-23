<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Utility\TextEdit;

/**
 * Provides keyword autocompletion suggestions at a specific location in a file.
 *
 * NOTE: Compile-time constants are already provided by the stubs from jetbrains/phpstorm-stubs.
 *
 * @see https://secure.php.net/manual/en/reserved.keywords.php
 * @see https://secure.php.net/manual/en/reserved.other-reserved-words.php
 */
final class KeywordAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        foreach ($this->getKeywords() as $keyword) {
            yield $this->createSuggestion($keyword, $context);
        }
    }

    /**
     * @param array                         $keyword
     * @param AutocompletionProviderContext $context
     *
     * @return CompletionItem
     */
    private function createSuggestion(array $keyword, AutocompletionProviderContext $context): CompletionItem
    {
        return new CompletionItem(
            $keyword['name'],
            CompletionItemKind::KEYWORD,
            $keyword['name'],
            $this->getTextEditForSuggestion($keyword, $context),
            $keyword['name'],
            'PHP keyword',
            [],
            [],
            false
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
     * @param array                         $keyword
     * @param AutocompletionProviderContext $context
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(array $keyword, AutocompletionProviderContext $context): TextEdit
    {
        return new TextEdit($context->getPrefixRange(), $keyword['name']);
    }

    /**
     * @return array
     */
    private function getKeywords(): array
    {
        return [
            ['name' => 'self'],
            ['name' => 'static'],
            ['name' => 'parent'],
            ['name' => 'int'],
            ['name' => 'float'],
            ['name' => 'bool'],
            ['name' => 'string'],
            ['name' => 'true'],
            ['name' => 'false'],
            ['name' => 'null'],
            ['name' => 'void'],
            ['name' => 'iterable'],
            ['name' => '__halt_compiler'],
            ['name' => 'abstract'],
            ['name' => 'and'],
            ['name' => 'array'],
            ['name' => 'as'],
            ['name' => 'break'],
            ['name' => 'callable'],
            ['name' => 'case'],
            ['name' => 'catch'],
            ['name' => 'class'],
            ['name' => 'clone'],
            ['name' => 'const'],
            ['name' => 'continue'],
            ['name' => 'declare'],
            ['name' => 'default'],
            ['name' => 'die'],
            ['name' => 'do'],
            ['name' => 'echo'],
            ['name' => 'else'],
            ['name' => 'elseif'],
            ['name' => 'empty'],
            ['name' => 'enddeclare'],
            ['name' => 'endfor'],
            ['name' => 'endforeach'],
            ['name' => 'endif'],
            ['name' => 'endswitch'],
            ['name' => 'endwhile'],
            ['name' => 'eval'],
            ['name' => 'exit'],
            ['name' => 'extends'],
            ['name' => 'final'],
            ['name' => 'finally'],
            ['name' => 'for'],
            ['name' => 'foreach'],
            ['name' => 'function'],
            ['name' => 'global'],
            ['name' => 'goto'],
            ['name' => 'if'],
            ['name' => 'implements'],
            ['name' => 'include'],
            ['name' => 'include_once'],
            ['name' => 'instanceof'],
            ['name' => 'insteadof'],
            ['name' => 'interface'],
            ['name' => 'isset'],
            ['name' => 'list'],
            ['name' => 'namespace'],
            ['name' => 'new'],
            ['name' => 'or'],
            ['name' => 'print'],
            ['name' => 'private'],
            ['name' => 'protected'],
            ['name' => 'public'],
            ['name' => 'require'],
            ['name' => 'require_once'],
            ['name' => 'return'],
            ['name' => 'static'],
            ['name' => 'switch'],
            ['name' => 'throw'],
            ['name' => 'trait'],
            ['name' => 'try'],
            ['name' => 'unset'],
            ['name' => 'use'],
            ['name' => 'var'],
            ['name' => 'while'],
            ['name' => 'xor'],
            ['name' => 'yield'],
        ];
    }
}
