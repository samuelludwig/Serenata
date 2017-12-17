<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;

class LocalVariableAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllConstants(): void
    {
        $output = $this->provide('LocalVariable.phpt');

        $suggestions = [
            new AutocompletionSuggestion('$foo', SuggestionKind::VARIABLE, '$foo', '$foo', null, [
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
