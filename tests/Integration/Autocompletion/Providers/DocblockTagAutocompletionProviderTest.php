<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

class DocblockTagAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllTags(): void
    {
        $output = $this->provide('DocblockTags.phpt');

        $firstSuggestion =
            new AutocompletionSuggestion(
                '@api',
                SuggestionKind::KEYWORD,
                '@api$0',
                new TextEdit(
                    new Range(new Position(3, 3), new Position(3, 4)),
                    '@api$0'
                ),
                '@api',
                'PHP docblock tag',
                [
                    'returnTypes'  => '',
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
        return 'DocblockTagAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'docblockTagAutocompletionProvider';
    }
}
