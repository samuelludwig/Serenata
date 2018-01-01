<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;

class SuperglobalAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllKeywords(): void
    {
        $output = $this->provide('Superglobals.phpt');

        $firstSuggestion =
            new AutocompletionSuggestion('$argc', SuggestionKind::VARIABLE, '$argc', null, '$argc', 'PHP superglobal', [
                'isDeprecated' => false,
                'returnTypes'  => 'int'
            ]);

        static::assertEquals($firstSuggestion, $output[0]);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'SuperglobalAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'superglobalAutocompletionProvider';
    }
}
