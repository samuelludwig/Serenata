<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

class KeywordAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllKeywords(): void
    {
        $output = $this->provide('Keywords.phpt');

        $firstSuggestion =
            new AutocompletionSuggestion(
                'self',
                SuggestionKind::KEYWORD,
                'self',
                new TextEdit(
                    new Range(
                        new Position(2, 0),
                        new Position(2, 0)
                    ),
                    'self'
                ),
                'self',
                'PHP keyword',
                [
                    'returnTypes'  => ''
                ],
                [],
                false
            );

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
