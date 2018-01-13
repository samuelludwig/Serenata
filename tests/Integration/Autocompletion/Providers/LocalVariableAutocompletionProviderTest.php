<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion\Providers;

use PhpIntegrator\Autocompletion\SuggestionKind;
use PhpIntegrator\Autocompletion\AutocompletionSuggestion;

class LocalVariableAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllConstants(): void
    {
        $output = $this->provide('LocalVariable.phpt');

        $suggestions = [
            new AutocompletionSuggestion('$foo', SuggestionKind::VARIABLE, '$foo', null, '$foo', null, [
                'isDeprecated' => false,
                'returnTypes'  => ''
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'LocalVariableAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'localVariableAutocompletionProvider';
    }
}
