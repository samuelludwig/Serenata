<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

class ClassConstantAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllProperties(): void
    {
        $fileName = 'ClassConstant.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'FOO',
                SuggestionKind::CONSTANT,
                'FOO',
                new TextEdit(
                    new Range(
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'FOO'
                ),
                'FOO',
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

        static::assertCount(2, $output);
        static::assertEquals($suggestions[0], $output[1]);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedClassConstantAsDeprecated(): void
    {
        $fileName = 'DeprecatedClassConstant.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'FOO',
                SuggestionKind::CONSTANT,
                'FOO',
                new TextEdit(
                    new Range(
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'FOO'
                ),
                'FOO',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes'        => 'int',
                ],
                [],
                true,
                'A'
            ),
        ];

        static::assertCount(2, $output);
        static::assertEquals($suggestions[0], $output[1]);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'ClassConstantAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'classConstantAutocompletionProvider';
    }
}
