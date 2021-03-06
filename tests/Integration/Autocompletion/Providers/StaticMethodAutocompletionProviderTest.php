<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

final class StaticMethodAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllMethods(): void
    {
        $fileName = 'StaticMethod.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::METHOD,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(13, 3),
                        new Position(13, 3)
                    ),
                    'foo()$0'
                ),
                'foo()',
                null,
                [],
                false,
                'int|string — public — A'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByParanthesis(): void
    {
        $fileName = 'CursorFollowedByParanthesis.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::METHOD,
                'foo',
                new TextEdit(
                    new Range(
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'foo'
                ),
                'foo()',
                null,
                [],
                false,
                'mixed — public — A'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByWhitespaceAndParanthesis(): void
    {
        $fileName = 'CursorFollowedByWhitespaceAndParanthesis.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::METHOD,
                'foo',
                new TextEdit(
                    new Range(
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'foo'
                ),
                'foo()',
                null,
                [],
                false,
                'mixed — public — A'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedMethodAsDeprecated(): void
    {
        $fileName = 'DeprecatedMethod.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::METHOD,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(13, 3),
                        new Position(13, 3)
                    ),
                    'foo()$0'
                ),
                'foo()',
                null,
                [],
                true,
                'void — public — A'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorOutsideOfParanthesesIfNoRequiredParametersExist(): void
    {
        $fileName = 'NoRequiredParameters.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::METHOD,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'foo()$0'
                ),
                'foo([$i = 0])',
                null,
                [],
                false,
                'mixed — public — A'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorInsideOfParanthesesIfRequiredParametersExist(): void
    {
        $fileName = 'RequiredParameters.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new CompletionItem(
                'foo',
                CompletionItemKind::METHOD,
                'foo($0)',
                new TextEdit(
                    new Range(
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'foo($0)'
                ),
                'foo($test)',
                null,
                [],
                false,
                'mixed — public — A'
            ),
        ];

        self::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testDoesNotReturnNonStaticMethod(): void
    {
        $fileName = 'Method.phpt';

        $output = $this->provide($fileName);

        self::assertEquals([], $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'StaticMethodAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'staticMethodAutocompletionProvider';
    }
}
