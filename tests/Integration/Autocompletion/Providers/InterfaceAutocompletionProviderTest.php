<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion\Providers;

use PhpIntegrator\Autocompletion\SuggestionKind;
use PhpIntegrator\Autocompletion\AutocompletionSuggestion;

use PhpIntegrator\Common\Range;
use PhpIntegrator\Common\Position;

use PhpIntegrator\Indexing\Structures\ClasslikeTypeNameValue;

use PhpIntegrator\Utility\TextEdit;

class InterfaceAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testUsesClassType(): void
    {
        $output = $this->provide('Interface.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '\Foo',
                SuggestionKind::CLASS_,
                'Foo',
                new TextEdit(
                    new Range(new Position(7, 0), new Position(7, 0)),
                    'Foo'
                ),
                'Foo',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => ClasslikeTypeNameValue::INTERFACE_,
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
