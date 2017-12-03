<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;

class FunctionAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllFunctions(): void
    {
        $output = $this->provide('Functions.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo()', 'foo()', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => null,
                'declaringStructure'            => null,
                'url'                           => null,
                'returnTypes'                   => 'int|string',
                'placeCursorBetweenParentheses' => false
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
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo', 'foo()', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => null,
                'declaringStructure'            => null,
                'url'                           => null,
                'returnTypes'                   => '',
                'placeCursorBetweenParentheses' => false
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
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo', 'foo()', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => null,
                'declaringStructure'            => null,
                'url'                           => null,
                'returnTypes'                   => '',
                'placeCursorBetweenParentheses' => false
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSuggestsPlacingCursorBetweenParanthesesWhenParametersExist(): void
    {
        $output = $this->provide('FunctionWithParameters.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo()', 'foo($test)', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => null,
                'declaringStructure'            => null,
                'url'                           => null,
                'returnTypes'                   => '',
                'placeCursorBetweenParentheses' => true
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
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo()', 'foo()', null, [
                'isDeprecated'                  => true,
                'protectionLevel'               => null,
                'declaringStructure'            => null,
                'url'                           => null,
                'returnTypes'                   => 'void',
                'placeCursorBetweenParentheses' => false
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
