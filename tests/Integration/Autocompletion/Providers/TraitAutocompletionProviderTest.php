<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures\ClasslikeTypeNameValue;

use Serenata\Utility\TextEdit;

class TraitAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testUsesMixinType(): void
    {
        $output = $this->provide('Trait.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo',
                CompletionItemKind::CLASS_,
                'Foo',
                new TextEdit(
                    new Range(new Position(7, 0), new Position(7, 1)),
                    'Foo'
                ),
                'Foo',
                null,
                [],
                false,
                ClasslikeTypeNameValue::TRAIT_
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'TraitAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'traitAutocompletionProvider';
    }
}
