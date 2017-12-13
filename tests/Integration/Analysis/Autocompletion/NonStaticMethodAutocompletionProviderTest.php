<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;

class NonStaticMethodAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllMethods(): void
    {
        $fileName = 'Methods.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo()', 'foo()', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => 'public',
                'declaringStructure'            => [
                    'fqcn'            => '\A',
                    'filename'        => $this->getPathFor($fileName),
                    'startLine'       => 3,
                    'endLine'         => 12,
                    'type'            => 'class',
                    'startLineMember' => 8,
                    'endLineMember'   => 11,
                ],
                'returnTypes'                   => 'int|string',
                'placeCursorBetweenParentheses' => false
            ])
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
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo', 'foo()', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => 'public',
                'declaringStructure'            => [
                    'fqcn'            => '\A',
                    'filename'        => $this->getPathFor($fileName),
                    'startLine'       => 3,
                    'endLine'         => 9,
                    'type'            => 'class',
                    'startLineMember' => 5,
                    'endLineMember'   => 8,
                ],
                'returnTypes'                   => '',
                'placeCursorBetweenParentheses' => false
            ])
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
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo', 'foo()', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => 'public',
                'declaringStructure'            => [
                    'fqcn'            => '\A',
                    'filename'        => $this->getPathFor($fileName),
                    'startLine'       => 3,
                    'endLine'         => 9,
                    'type'            => 'class',
                    'startLineMember' => 5,
                    'endLineMember'   => 8,
                ],
                'returnTypes'                   => '',
                'placeCursorBetweenParentheses' => false
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSuggestsPlacingCursorBetweenParanthesesWhenParametersExist(): void
    {
        $fileName = 'MethodWithParameters.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo()', 'foo($test)', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => 'public',
                'declaringStructure'            => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 9,
                        'type'            => 'class',
                        'startLineMember' => 5,
                        'endLineMember'   => 8,
                    ],
                'returnTypes'                   => '',
                'placeCursorBetweenParentheses' => true
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedFunctionAsDeprecated(): void
    {
        $fileName = 'DeprecatedMethod.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::METHOD, 'foo()', 'foo()', null, [
                'isDeprecated'                  => true,
                'protectionLevel'               => 'public',
                'declaringStructure'            => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 12,
                        'type'            => 'class',
                        'startLineMember' => 8,
                        'endLineMember'   => 11,
                    ],
                'returnTypes'                   => 'void',
                'placeCursorBetweenParentheses' => false
            ])
        ];

        static::assertEquals($suggestions, $output);
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
