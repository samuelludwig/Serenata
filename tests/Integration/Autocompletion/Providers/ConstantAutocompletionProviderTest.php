<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

final class ConstantAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllConstants(): void
    {
        $output = $this->provide('Constants.phpt');

        $suggestions = [
            new CompletionItem(
                '\FOO',
                CompletionItemKind::CONSTANT,
                'FOO',
                new TextEdit(
                    new Range(
                        new Position(7, 0),
                        new Position(7, 1)
                    ),
                    'FOO'
                ),
                'FOO',
                null,
                [],
                false,
                'int|string — FOO'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedConstantAsDeprecated(): void
    {
        $output = $this->provide('DeprecatedConstant.phpt');

        $suggestions = [
            new CompletionItem(
                '\FOO',
                CompletionItemKind::CONSTANT,
                'FOO',
                new TextEdit(
                    new Range(
                        new Position(7, 0),
                        new Position(7, 1)
                    ),
                    'FOO'
                ),
                'FOO',
                null,
                [],
                true,
                'int — FOO'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testIncludesClassUseStatementImportInSuggestionForFullyQualifiedConstantNamesWithoutLeadingSlash(): void
    {
        $output = $this->provide('NamespacedConstantFullyQualifiedNoLeadingSlash.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo\Bar\BAZ',
                CompletionItemKind::CONSTANT,
                'BAZ',
                new TextEdit(
                    new Range(new Position(7, 4), new Position(7, 5)),
                    'BAZ'
                ),
                'BAZ',
                null,
                [
                    new TextEdit(
                        new Range(new Position(7, 0), new Position(7, 0)),
                        "use const Foo\Bar\BAZ;\n\n"
                    ),
                ],
                false,
                'int — Foo\Bar\BAZ'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testIncludesClassUseStatementImportInSuggestionForPartiallyQualifiedConstantNames(): void
    {
        $output = $this->provide('NamespacedConstantPartiallyQualified.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo\Bar\BAZ',
                CompletionItemKind::CONSTANT,
                'Bar\\\\BAZ',
                new TextEdit(
                    new Range(new Position(7, 4), new Position(7, 8)),
                    'Bar\\\\BAZ'
                ),
                'BAZ',
                null,
                [
                    new TextEdit(
                        new Range(new Position(7, 0), new Position(7, 0)),
                        "use Foo\Bar;\n\n"
                    ),
                ],
                false,
                'int — Foo\Bar\BAZ'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSkipsUseStatementImportInSuggestionForFullyQualifiedConstantNamesWithLeadingSlash(): void
    {
        $output = $this->provide('NamespacedConstantFullyQualifiedLeadingSlash.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo\Bar\BAZ',
                CompletionItemKind::CONSTANT,
                '\\\\Foo\\\\Bar\\\\BAZ',
                new TextEdit(
                    new Range(new Position(7, 4), new Position(7, 6)),
                    '\\\\Foo\\\\Bar\\\\BAZ'
                ),
                'BAZ',
                null,
                [],
                false,
                'int — Foo\Bar\BAZ'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSkipsUseStatementImportWhenAutocompletingUseStatement(): void
    {
        $output = $this->provide('UseStatement.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo\Bar\Baz\QUX',
                CompletionItemKind::CONSTANT,
                'Foo\\\\Bar\\\\Baz\\\\QUX',
                new TextEdit(
                    new Range(new Position(7, 14), new Position(7, 21)),
                    'Foo\\\\Bar\\\\Baz\\\\QUX'
                ),
                'QUX',
                null,
                [],
                false,
                'int — Foo\Bar\Baz\QUX'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSkipsUseStatementImportForGlobalConstants(): void
    {
        $output = $this->provide('GlobalConstant.phpt');

        $suggestions = [
            new CompletionItem(
                '\BAZ',
                CompletionItemKind::CONSTANT,
                'BAZ',
                new TextEdit(
                    new Range(new Position(7, 4), new Position(7, 4)),
                    'BAZ'
                ),
                'BAZ',
                null,
                [],
                false,
                'int — BAZ'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'ConstantAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'constantAutocompletionProvider';
    }
}
