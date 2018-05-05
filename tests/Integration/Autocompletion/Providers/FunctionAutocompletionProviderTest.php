<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

class FunctionAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllFunctions(): void
    {
        $output = $this->provide('Functions.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo($0)', null, 'foo()', null, [
                'isDeprecated' => false,
                'returnTypes'  => 'int|string'
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByParanthesis(): void
    {
        $output = $this->provide('CursorFollowedByParanthesis.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo', null, 'foo()', null, [
                'isDeprecated' => false,
                'returnTypes'  => ''
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByWhitespaceAndParanthesis(): void
    {
        $output = $this->provide('CursorFollowedByWhitespaceAndParanthesis.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo', null, 'foo()', null, [
                'isDeprecated' => false,
                'returnTypes'  => ''
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedFunctionAsDeprecated(): void
    {
        $output = $this->provide('DeprecatedFunction.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo($0)', null, 'foo()', null, [
                'isDeprecated' => true,
                'returnTypes'  => 'void'
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'FunctionAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'functionAutocompletionProvider';
    }
}
