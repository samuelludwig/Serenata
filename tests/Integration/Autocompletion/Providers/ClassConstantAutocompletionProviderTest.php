<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

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
            new AutocompletionSuggestion('FOO', SuggestionKind::CONSTANT, 'FOO', null, 'FOO', null, [
                'protectionLevel'    => 'public',
                'declaringStructure' => [
                    'fqcn'            => '\A',
                    'filename'        => $this->getPathFor($fileName),
                    'startLine'       => 3,
                    'endLine'         => 9,
                    'type'            => 'class',
                    'startLineMember' => 8,
                    'endLineMember'   => 8,
                ],
                'returnTypes'        => 'int|string'
            ], [], false)
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
            new AutocompletionSuggestion('FOO', SuggestionKind::CONSTANT, 'FOO', null, 'FOO', null, [
                'protectionLevel'    => 'public',
                'declaringStructure' => [
                        'fqcn'            => '\A',
                        'filename'        => $this->getPathFor($fileName),
                        'startLine'       => 3,
                        'endLine'         => 9,
                        'type'            => 'class',
                        'startLineMember' => 8,
                        'endLineMember'   => 8,
                    ],
                'returnTypes'        => 'int'
            ], [], true)
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
