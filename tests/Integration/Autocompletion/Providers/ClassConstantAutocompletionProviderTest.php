<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

final class ClassConstantAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllProperties(): void
    {
        $fileName = 'ClassConstant.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'FOO',
                CompletionItemKind::CONSTANT,
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
                [],
                false,
                'int|string — public — A'
            ),
        ];

        self::assertCount(2, $output);
        self::assertEquals($suggestions[0], $output[1]);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedClassConstantAsDeprecated(): void
    {
        $fileName = 'DeprecatedClassConstant.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'FOO',
                CompletionItemKind::CONSTANT,
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
                [],
                true,
                'int — public — A'
            ),
        ];

        self::assertCount(2, $output);
        self::assertEquals($suggestions[0], $output[1]);
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
