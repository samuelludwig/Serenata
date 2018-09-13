<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

class NonStaticMethodAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllMethods(): void
    {
        $fileName = 'Method.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'foo',
                SuggestionKind::METHOD,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(14, 4),
                        new Position(14, 4)
                    ),
                    'foo()$0'
                ),
                'foo()',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes' => 'int|string',
                ],
                [],
                false,
                'A'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByParanthesis(): void
    {
        $fileName = 'CursorFollowedByParanthesis.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'foo',
                SuggestionKind::METHOD,
                'foo',
                new TextEdit(
                    new Range(
                        new Position(11, 4),
                        new Position(11, 4)
                    ),
                    'foo'
                ),
                'foo()',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes' => 'mixed',
                ],
                [],
                false,
                'A'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByWhitespaceAndParanthesis(): void
    {
        $fileName = 'CursorFollowedByWhitespaceAndParanthesis.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'foo',
                SuggestionKind::METHOD,
                'foo',
                new TextEdit(
                    new Range(
                        new Position(11, 4),
                        new Position(11, 4)
                    ),
                    'foo'
                ),
                'foo()',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes' => 'mixed',
                ],
                [],
                false,
                'A'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedMethodAsDeprecated(): void
    {
        $fileName = 'DeprecatedMethod.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'foo',
                SuggestionKind::METHOD,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(14, 4),
                        new Position(14, 4)
                    ),
                    'foo()$0'
                ),
                'foo()',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes' => 'void',
                ],
                [],
                true,
                'A'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorOutsideOfParanthesesIfNoRequiredParametersExist(): void
    {
        $fileName = 'NoRequiredParameters.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'foo',
                SuggestionKind::METHOD,
                'foo()$0',
                new TextEdit(
                    new Range(
                        new Position(11, 4),
                        new Position(11, 4)
                    ),
                    'foo()$0'
                ),
                'foo([$i = 0])',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes'        => 'mixed',
                ],
                [],
                false,
                'A'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMovesCursorInsideOfParanthesesIfRequiredParametersExist(): void
    {
        $fileName = 'RequiredParameters.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'foo',
                SuggestionKind::METHOD,
                'foo($0)',
                new TextEdit(
                    new Range(
                        new Position(11, 4),
                        new Position(11, 4)
                    ),
                    'foo($0)'
                ),
                'foo($test)',
                null,
                [
                    'protectionLevel'    => 'public',
                    'returnTypes'        => 'mixed',
                ],
                [],
                false,
                'A'
            ),
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testDoesNotReturnStaticMethod(): void
    {
        $fileName = 'StaticMethod.phpt';

        $output = $this->provide($fileName);

        static::assertEquals([], $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'NonStaticMethodAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'nonStaticMethodAutocompletionProvider';
    }
}
