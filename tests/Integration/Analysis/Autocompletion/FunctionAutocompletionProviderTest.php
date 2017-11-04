<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;
use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class FunctionAutocompletionProviderTest extends AbstractIntegrationTest
{
    /**
     * @param string $file
     *
     * @return string[]
     */
    protected function provide(string $file): array
    {
        $path = __DIR__ . '/FunctionAutocompletionProviderTest/' . $file;

        $markerOffset = $this->getMarkerOffset($path, '<MARKER>');

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);

        $provider = $container->get('functionAutocompletionProvider');

        return iterator_to_array($provider->provide($path, $markerOffset), false);
    }

    /**
     * @param string $path
     * @param string $marker
     *
     * @return int
     */
    protected function getMarkerOffset(string $path, string $marker): int
    {
        $testFileContents = @file_get_contents($path);

        $markerOffset = mb_strpos($testFileContents, $marker);

        return $markerOffset;
    }

    /**
     * @return void
     */
    public function testRetrievesAllFunctions(): void
    {
        $output = $this->provide('Functions.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo()', 'foo()', null, [
                'isDeprecated'                  => false,
                'protectionLevel'               => null,
                'declaringStructure'            => null,
                'url'                           => null,
                'returnTypes'                   => '',
                'placeCursorBetweenParentheses' => false
            ])
        ];

        static::assertEquals($suggestions, $output);
    }
}
