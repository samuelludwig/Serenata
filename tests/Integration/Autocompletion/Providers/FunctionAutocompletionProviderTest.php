<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

class FunctionAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllFunctions(): void
    {
        $output = $this->provide('Functions.phpt');

        $suggestions = [
            new CompletionItem(
                'foo',
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
                'int|string'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByParanthesis(): void
    {
        $output = $this->provide('CursorFollowedByParanthesis.phpt');

        $suggestions = [
            new CompletionItem(
                'foo',
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
                'mixed'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByWhitespaceAndParanthesis(): void
    {
        $output = $this->provide('CursorFollowedByWhitespaceAndParanthesis.phpt');

        $suggestions = [
            new CompletionItem(
                'foo',
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
                'mixed'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedFunctionAsDeprecated(): void
    {
        $output = $this->provide('DeprecatedFunction.phpt');

        $suggestions = [
            new CompletionItem(
                'foo',
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
                'void'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorOutsideOfParanthesesIfNoRequiredParametersExist(): void
    {
        $output = $this->provide('NoRequiredParameters.phpt');

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::FUNCTION,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(7, 0),
                        new Position(7, 1)
                    ),
                    'foo()$0'
                ),
                'foo([$i])',
                null,
                [],
                false,
                'mixed'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorInsideOfParanthesesIfRequiredParametersExist(): void
    {
        $output = $this->provide('RequiredParameters.phpt');

        $suggestions = [
            new CompletionItem(
                'foo',
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
                'mixed'
            ),
        ];

        static::assertEquals($suggestions, $output);
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
