<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

class NonStaticPropertyAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllProperties(): void
    {
        $fileName = 'Property.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::PROPERTY,
                'foo',
                new TextEdit(
                    new Range(
                        new Position(11, 4),
                        new Position(11, 4)
                    ),
                    'foo'
                ),
                'foo',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes'        => 'int|string',
                ],
                [],
                false,
                'A'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedPropertyAsDeprecated(): void
    {
        $fileName = 'DeprecatedProperty.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::PROPERTY,
                'foo',
                new TextEdit(
                    new Range(
                        new Position(11, 4),
                        new Position(11, 4)
                    ),
                    'foo'
                ),
                'foo',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes'        => 'mixed',
                ],
                [],
                true,
                'A'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testDoesNotReturnStaticProperty(): void
    {
        $fileName = 'StaticProperty.phpt';

        $output = $this->provide($fileName);

        static::assertEquals([], $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'NonStaticPropertyAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'nonStaticPropertyAutocompletionProvider';
    }
}
