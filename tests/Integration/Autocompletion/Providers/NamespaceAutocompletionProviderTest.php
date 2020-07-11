<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

final class NamespaceAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAll(): void
    {
        $output = $this->provide('Namespace.phpt');

        $suggestions = [
            new CompletionItem(
                'Foo',
                CompletionItemKind::MODULE,
                'Foo',
                new TextEdit(
                    new Range(
                        new Position(7, 0),
                        new Position(7, 1)
                    ),
                    'Foo'
                ),
                'Foo',
                null,
                [],
                false,
                'namespace'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsAnonymousNamespaces(): void
    {
        $output = $this->provide('AnonymousNamespace.phpt');

        self::assertEquals([], $output);
    }

    /**
     * @return void
     */
    public function testDeduplicatesNames(): void
    {
        $output = $this->provide('Namespaces.phpt');

        $suggestions = [
            new CompletionItem(
                'Foo',
                CompletionItemKind::MODULE,
                'Foo',
                new TextEdit(
                    new Range(
                        new Position(12, 0),
                        new Position(12, 1)
                    ),
                    'Foo'
                ),
                'Foo',
                null,
                [],
                false,
                'namespace'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'NamespaceAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'namespaceAutocompletionProvider';
    }
}
