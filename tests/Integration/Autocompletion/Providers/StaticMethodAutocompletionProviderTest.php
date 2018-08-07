<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

class StaticMethodAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllMethods(): void
    {
        $fileName = 'StaticMethod.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                'foo',
                SuggestionKind::METHOD,
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
                [
                    'protectionLevel'    => 'public',
                    'declaringStructure' => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 12,
                        'type'            => 'class',
                        'startLineMember' => 8,
                        'endLineMember'   => 11,
                    ],
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
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'foo'
                ),
                'foo()',
                null,
                [
                    'protectionLevel'    => 'public',
                    'declaringStructure' => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 9,
                        'type'            => 'class',
                        'startLineMember' => 5,
                        'endLineMember'   => 8,
                    ],
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
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'foo'
                ),
                'foo()',
                null,
                [
                    'protectionLevel'    => 'public',
                    'declaringStructure' => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 9,
                        'type'            => 'class',
                        'startLineMember' => 5,
                        'endLineMember'   => 8,
                    ],
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
                        new Position(13, 3),
                        new Position(13, 3)
                    ),
                    'foo()$0'
                ),
                'foo()',
                null,
                [
                    'protectionLevel'    => 'public',
                    'declaringStructure' => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 12,
                        'type'            => 'class',
                        'startLineMember' => 8,
                        'endLineMember'   => 11,
                    ],
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
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'foo()$0'
                ),
                'foo([$i])',
                null,
                [
                    'protectionLevel'    => 'public',
                    'declaringStructure' => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 9,
                        'type'            => 'class',
                        'startLineMember' => 5,
                        'endLineMember'   => 8,
                    ],
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
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    'foo($0)'
                ),
                'foo($test)',
                null,
                [
                    'protectionLevel'    => 'public',
                    'declaringStructure' => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 9,
                        'type'            => 'class',
                        'startLineMember' => 5,
                        'endLineMember'   => 8,
                    ],
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
    public function testDoesNotReturnNonStaticMethod(): void
    {
        $fileName = 'Method.phpt';

        $output = $this->provide($fileName);

        static::assertEquals([], $output);
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
