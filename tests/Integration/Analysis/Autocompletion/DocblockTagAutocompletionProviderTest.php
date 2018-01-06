<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Common\Range;
use PhpIntegrator\Common\Position;

use PhpIntegrator\Utility\TextEdit;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;

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
                    'isDeprecated' => false,
                    'returnTypes'  => '',
                    'prefix'       => '@'
                ]
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
