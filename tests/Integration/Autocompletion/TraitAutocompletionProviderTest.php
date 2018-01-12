<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion;

use PhpIntegrator\Autocompletion\SuggestionKind;
use PhpIntegrator\Autocompletion\AutocompletionSuggestion;

use PhpIntegrator\Common\Range;
use PhpIntegrator\Common\Position;

use PhpIntegrator\Indexing\Structures\ClasslikeTypeNameValue;

use PhpIntegrator\Utility\TextEdit;

class ClasslikeAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testUsesMixinType(): void
    {
        $output = $this->provide('Trait.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '\Foo',
                SuggestionKind::MIXIN,
                'Foo',
                new TextEdit(
                    new Range(new Position(7, 0), new Position(7, 0)),
                    'Foo'
                ),
                'Foo',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => ClasslikeTypeNameValue::TRAIT_,
                    'prefix'       => ''
                ]
            )
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
