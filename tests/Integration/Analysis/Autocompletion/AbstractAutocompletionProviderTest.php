<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Indexing\Structures;

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
     * @param string $additionalFile
     *
     * @return string[]
     */
    protected function provide(string $file, ?string $additionalFile = null): array
    {
        $path = $this->getPathFor($file);

        $markerString = '// <MARKER>';

        $markerOffset = $this->getMarkerOffset($path, $markerString);

        $container = $this->createTestContainer();

        // Strip marker so it does not influence further processing.
        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);
        $code = str_replace($markerString, '', $code);

        $this->indexTestFileWithSource($container, $path, $code);

        if ($additionalFile !== null) {
            $this->indexTestFile($container, $this->getPathFor($additionalFile));
        }

        $provider = $container->get($this->getProviderName());

        $results = $provider->provide($this->getFileStub($path), $code, $markerOffset);

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

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getPathFor(string $fileName): string
    {
        return __DIR__ . '/' . $this->getFolderName() . '/' . $fileName;
    }

    /**
     * @param string $filePath
     *
     * @return Structures\File
     */
    private function getFileStub(string $filePath): Structures\File
    {
        return new Structures\File($filePath, new \DateTime(), []);
    }
}
