<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion;

use PhpIntegrator\Autocompletion\SuggestionKind;
use PhpIntegrator\Autocompletion\AutocompletionSuggestion;

class ConstantAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllConstants(): void
    {
        $output = $this->provide('Constants.phpt');

        $suggestions = [
            new AutocompletionSuggestion('FOO', SuggestionKind::CONSTANT, 'FOO', null, 'FOO', null, [
                'isDeprecated' => false,
                'returnTypes'  => 'int|string'
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedConstantAsDeprecated(): void
    {
        $output = $this->provide('DeprecatedConstant.phpt');

        $suggestions = [
            new AutocompletionSuggestion('FOO', SuggestionKind::CONSTANT, 'FOO', null, 'FOO', null, [
                'isDeprecated' => true,
                'returnTypes'  => 'int'
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'ConstantAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'constantAutocompletionProvider';
    }
}
