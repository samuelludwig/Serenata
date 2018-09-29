<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

class DocblockTagAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllTags(): void
    {
        $output = $this->provide('DocblockTags.phpt');

        $firstSuggestion =
            new CompletionItem(
                '@api',
                CompletionItemKind::KEYWORD,
                '@api$0',
                new TextEdit(
                    new Range(new Position(3, 3), new Position(3, 4)),
                    '@api$0'
                ),
                '@api',
                'PHP docblock tag',
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
