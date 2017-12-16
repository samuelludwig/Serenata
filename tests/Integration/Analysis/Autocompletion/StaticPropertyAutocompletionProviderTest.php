<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;

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
            new AutocompletionSuggestion('$foo', SuggestionKind::PROPERTY, '$foo', 'foo', null, [
                'isDeprecated'       => false,
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
            ])
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
            new AutocompletionSuggestion('$foo', SuggestionKind::PROPERTY, '$foo', 'foo', null, [
                'isDeprecated'       => true,
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
                'returnTypes'        => ''
            ])
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
