<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

class LocalVariableAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllConstants(): void
    {
        $output = $this->provide('LocalVariable.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '$foo',
                SuggestionKind::VARIABLE,
                '$foo',
                new TextEdit(
                    new Range(new Position(4, 0), new Position(4, 0)),
                    '$foo'
                ),
                '$foo',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => '',
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
        return 'LocalVariableAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'localVariableAutocompletionProvider';
    }
}
