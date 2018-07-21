<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

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
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo()$0', null, 'foo()', null, [
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
                'returnTypes' => 'int|string'
            ], [], false)
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
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo', null, 'foo()', null, [
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
                'returnTypes' => ''
            ], [], false)
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
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo', null, 'foo()', null, [
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
                'returnTypes' => ''
            ], [], false)
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
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo()$0', null, 'foo()', null, [
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
                'returnTypes' => 'void'
            ], [], true)
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
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo()$0', null, 'foo([$i])', null, [
                'returnTypes'        => '',
                'protectionLevel'    => 'public',
                'declaringStructure' => [
                    'fqcn'            => '\A',
                    'filename'        => $this->getPathFor($fileName),
                    'startLine'       => 3,
                    'endLine'         => 9,
                    'type'            => 'class',
                    'startLineMember' => 5,
                    'endLineMember'   => 8,
                ]
            ], [], false)
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
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo($0)', null, 'foo($test)', null, [
                'returnTypes'        => '',
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
            ], [], false)
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
