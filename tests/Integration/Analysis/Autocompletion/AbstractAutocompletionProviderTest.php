<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

/**
 * Abstract base class for autocompletion provider integration tests.
 */
abstract class AbstractAutocompletionProviderTest extends AbstractIntegrationTest
{
    /**
     * @return string
     */
    abstract protected function getFolderName(): string;

    /**
     * @return string
     */
    abstract protected function getProviderName(): string;

    /**
     * @param string $file
     *
     * @return string[]
     */
    protected function provide(string $file): array
    {
        $path = __DIR__ . '/' . $this->getFolderName() . '/' . $file;

        $markerString = '// <MARKER>';

        $markerOffset = $this->getMarkerOffset($path, $markerString);

        $container = $this->createTestContainer();

        // Strip marker so it does not influence further processing.
        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);
        $code = str_replace($markerString, '', $code);

        $this->indexTestFileWithSource($container, $path, $code);

        $provider = $container->get($this->getProviderName());

        $results = $provider->provide($code, $markerOffset);

        if (is_array($results)) {
            return $results;
        }

        return iterator_to_array($results, false);
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
}
