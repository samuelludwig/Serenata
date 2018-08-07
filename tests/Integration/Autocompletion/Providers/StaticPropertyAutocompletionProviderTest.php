<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

class StaticPropertyAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllProperties(): void
    {
        $fileName = 'StaticProperty.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                '$foo',
                SuggestionKind::PROPERTY,
                '$foo',
                new TextEdit(
                    new Range(
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    '$foo'
                ),
                'foo',
                null,
                [
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
                    'returnTypes'        => 'int|string',
                ],
                [],
                false,
                'A'
            )
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedPropertyAsDeprecated(): void
    {
        $fileName = 'DeprecatedProperty.phpt';

        $output = $this->provide($fileName);

        $suggestions = [
            new AutocompletionSuggestion(
                '$foo',
                SuggestionKind::PROPERTY,
                '$foo',
                new TextEdit(
                    new Range(
                        new Position(10, 3),
                        new Position(10, 3)
                    ),
                    '$foo'
                ),
                'foo',
                null,
                [
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
                    'returnTypes'        => 'mixed',
                ],
                [],
                true,
                'A'
            )
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testDoesNotReturnNonStaticProperty(): void
    {
        $fileName = 'Property.phpt';

        $output = $this->provide($fileName);

        static::assertEquals([], $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'StaticPropertyAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'staticPropertyAutocompletionProvider';
    }
}
