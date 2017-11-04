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

        $markerString = '// <MARKER>';

        $markerOffset = $this->getMarkerOffset($path, $markerString);

        $container = $this->createTestContainer();

        // Strip marker so it does not influence further processing.
        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);
        $code = str_replace($markerString, '', $code);

        $this->indexTestFileWithSource($container, $path, $code);

        $provider = $container->get('functionAutocompletionProvider');

        return iterator_to_array($provider->provide($code, $markerOffset), false);
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

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByParanthesis(): void
    {
        $output = $this->provide('CursorFollowedByParanthesis.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo', 'foo()', null, [
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

    /**
     * @return void
     */
    public function testOmitsParanthesesFromInsertionTextIfCursorIsFollowedByWhitespaceAndParanthesis(): void
    {
        $output = $this->provide('CursorFollowedByWhitespaceAndParanthesis.phpt');

        $suggestions = [
            new AutocompletionSuggestion('foo', SuggestionKind::FUNCTION, 'foo', 'foo()', null, [
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
