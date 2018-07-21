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
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo()$0', null, 'foo()', null, [
                'returnTypes'  => 'int|string'
            ], [], false)
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
                'returnTypes'  => ''
            ], [], false)
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
                'returnTypes'  => ''
            ], [], false)
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
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo()$0', null, 'foo()', null, [
                'returnTypes'  => 'void'
            ], [], true)
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorOutsideOfParanthesesIfNoRequiredParametersExist(): void
    {
        $output = $this->provide('NoRequiredParameters.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo()$0', null, 'foo([$i])', null, [
                'returnTypes'  => ''
            ], [], false)
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorInsideOfParanthesesIfRequiredParametersExist(): void
    {
        $output = $this->provide('RequiredParameters.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo($0)', null, 'foo($test)', null, [
                'returnTypes'  => ''
            ], [], false)
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
