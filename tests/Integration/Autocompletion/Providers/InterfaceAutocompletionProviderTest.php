<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures\ClasslikeTypeNameValue;

use Serenata\Utility\TextEdit;

final class InterfaceAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testUsesClassType(): void
    {
        $output = $this->provide('Interface.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo',
                CompletionItemKind::INTERFACE_,
                'Foo',
                new TextEdit(
                    new Range(new Position(7, 0), new Position(7, 1)),
                    'Foo'
                ),
                'Foo',
                null,
                [],
                false,
                ClasslikeTypeNameValue::INTERFACE_ . ' — Foo'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'InterfaceAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'interfaceAutocompletionProvider';
    }
}
