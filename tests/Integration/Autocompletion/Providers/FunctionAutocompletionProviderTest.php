<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

final class FunctionAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllFunctions(): void
    {
        $output = $this->provide('Functions.phpt');

        $suggestions = [
            new CompletionItem(
                '\foo',
                CompletionItemKind::FUNCTION,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(10, 0),
                        new Position(10, 1)
                    ),
                    'foo()$0'
                ),
                'foo()',
                null,
                [],
                false,
                'int|string — foo'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByParanthesis(): void
    {
        $output = $this->provide('CursorFollowedByParanthesis.phpt');

        $suggestions = [
            new CompletionItem(
                '\foo',
                CompletionItemKind::FUNCTION,
                'foo',
                new TextEdit(
                    new Range(
                        new Position(7, 0),
                        new Position(7, 1)
                    ),
                    'foo'
                ),
                'foo()',
                null,
                [],
                false,
                'mixed — foo'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByWhitespaceAndParanthesis(): void
    {
        $output = $this->provide('CursorFollowedByWhitespaceAndParanthesis.phpt');

        $suggestions = [
            new CompletionItem(
                '\foo',
                CompletionItemKind::FUNCTION,
                'foo',
                new TextEdit(
                    new Range(
                        new Position(7, 0),
                        new Position(7, 1)
                    ),
                    'foo'
                ),
                'foo()',
                null,
                [],
                false,
                'mixed — foo'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedFunctionAsDeprecated(): void
    {
        $output = $this->provide('DeprecatedFunction.phpt');

        $suggestions = [
            new CompletionItem(
                '\foo',
                CompletionItemKind::FUNCTION,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(10, 0),
                        new Position(10, 1)
                    ),
                    'foo()$0'
                ),
                'foo()',
                null,
                [],
                true,
                'void — foo'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorOutsideOfParanthesesIfNoRequiredParametersExist(): void
    {
        $output = $this->provide('NoRequiredParameters.phpt');

        $suggestions = [
            new CompletionItem(
                '\foo',
                CompletionItemKind::FUNCTION,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(7, 0),
                        new Position(7, 1)
                    ),
                    'foo()$0'
                ),
                'foo([$i = 0])',
                null,
                [],
                false,
                'mixed — foo'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorInsideOfParanthesesIfRequiredParametersExist(): void
    {
        $output = $this->provide('RequiredParameters.phpt');

        $suggestions = [
            new CompletionItem(
                '\foo',
                CompletionItemKind::FUNCTION,
                'foo($0)',
                new TextEdit(
                    new Range(
                        new Position(7, 0),
                        new Position(7, 1)
                    ),
                    'foo($0)'
                ),
                'foo($test)',
                null,
                [],
                false,
                'mixed — foo'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testIncludesClassUseStatementImportInSuggestionForFullyQualifiedFunctionNamesWithoutLeadingSlash(): void
    {
        $output = $this->provide('NamespacedFunctionFullyQualifiedNoLeadingSlash.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo\Bar\baz',
                CompletionItemKind::FUNCTION,
                'baz()$0',
                new TextEdit(
                    new Range(new Position(10, 4), new Position(10, 5)),
                    'baz()$0'
                ),
                'baz()',
                null,
                [
                    new TextEdit(
                        new Range(new Position(10, 0), new Position(10, 0)),
                        "use function Foo\Bar\baz;\n\n"
                    ),
                ],
                false,
                'mixed — Foo\Bar\baz'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testIncludesClassUseStatementImportInSuggestionForPartiallyQualifiedFunctionNames(): void
    {
        $output = $this->provide('NamespacedFunctionPartiallyQualified.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo\Bar\baz',
                CompletionItemKind::FUNCTION,
                'Bar\\\\baz()$0',
                new TextEdit(
                    new Range(new Position(10, 4), new Position(10, 8)),
                    'Bar\\\\baz()$0'
                ),
                'baz()',
                null,
                [
                    new TextEdit(
                        new Range(new Position(10, 0), new Position(10, 0)),
                        "use Foo\Bar;\n\n"
                    ),
                ],
                false,
                'mixed — Foo\Bar\baz'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSkipsUseStatementImportInSuggestionForFullyQualifiedFunctionNamesWithLeadingSlash(): void
    {
        $output = $this->provide('NamespacedFunctionFullyQualifiedLeadingSlash.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo\Bar\baz',
                CompletionItemKind::FUNCTION,
                '\\\\Foo\\\\Bar\\\\baz()$0',
                new TextEdit(
                    new Range(new Position(10, 4), new Position(10, 6)),
                    '\\\\Foo\\\\Bar\\\\baz()$0'
                ),
                'baz()',
                null,
                [],
                false,
                'mixed — Foo\Bar\baz'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSkipsUseStatementImportWhenAutocompletingUseStatement(): void
    {
        $output = $this->provide('UseStatement.phpt');

        $suggestions = [
            new CompletionItem(
                '\Foo\Bar\Baz\qux',
                CompletionItemKind::FUNCTION,
                'Foo\\\\Bar\\\\Baz\\\\qux',
                new TextEdit(
                    new Range(new Position(10, 17), new Position(10, 24)),
                    'Foo\\\\Bar\\\\Baz\\\\qux'
                ),
                'qux()',
                null,
                [],
                false,
                'mixed — Foo\Bar\Baz\qux'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSkipsUseStatementImportForGlobalFunctions(): void
    {
        $output = $this->provide('GlobalFunction.phpt');

        $suggestions = [
            new CompletionItem(
                '\baz',
                CompletionItemKind::FUNCTION,
                'baz()$0',
                new TextEdit(
                    new Range(new Position(10, 4), new Position(10, 4)),
                    'baz()$0'
                ),
                'baz()',
                null,
                [],
                false,
                'mixed — baz'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'FunctionAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'functionAutocompletionProvider';
    }
}
