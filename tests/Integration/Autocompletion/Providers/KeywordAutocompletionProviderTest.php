<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion\Providers;

use PhpIntegrator\Autocompletion\SuggestionKind;
use PhpIntegrator\Autocompletion\AutocompletionSuggestion;

class KeywordAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllKeywords(): void
    {
        $output = $this->provide('Keywords.phpt');

        $firstSuggestion =
            new AutocompletionSuggestion('self', SuggestionKind::KEYWORD, 'self', null, 'self', 'PHP keyword', [
                'isDeprecated' => false,
                'returnTypes'  => ''
            ]);

        static::assertEquals($firstSuggestion, $output[0]);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'KeywordAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'keywordAutocompletionProvider';
    }
}
