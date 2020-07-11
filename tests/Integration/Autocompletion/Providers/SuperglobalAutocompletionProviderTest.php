<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

final class SuperglobalAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllKeywords(): void
    {
        $output = $this->provide('Superglobals.phpt');

        $firstSuggestion = new CompletionItem(
            '$argc',
            CompletionItemKind::VARIABLE,
            '$argc',
            new TextEdit(
                new Range(
                    new Position(2, 0),
                    new Position(2, 1)
                ),
                '\$argc'
            ),
            'argc',
            'PHP superglobal',
            [],
            false,
            'int'
        );

        self::assertEquals($firstSuggestion, $output[0]);
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
