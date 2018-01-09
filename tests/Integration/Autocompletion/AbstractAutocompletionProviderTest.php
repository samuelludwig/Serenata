<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion;

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
     * @param string      $file
     * @param string|null $additionalFile
     * @param string      $injectionPoint
     *
     * @return string[]
     */
    protected function provide(string $file, ?string $additionalFile = null, string $injectionPoint = '<?php'): array
    {
        $path = $this->getPathFor($file);

        $container = $this->createTestContainer();

        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        if ($additionalFile !== null) {
            $additionalCode = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile(
                $this->getPathFor($additionalFile)
            );

            $code = str_replace($injectionPoint, $additionalCode, $code, $count);

            static::assertGreaterThan(0, $count, 'Injection point for additional code not found in file ' . $file);
        }

        $markerString = '// <MARKER>';

        $markerOffset = $this->getMarkerOffset($code, $markerString);

        // Strip marker so it does not influence further processing.
        $code = str_replace($markerString, '', $code);

        $this->indexTestFileWithSource($container, $path, $code);

        $provider = $container->get($this->getProviderName());

        $results = $provider->provide(
            $container->get('storage')->getFileByPath($path),
            $code,
            $markerOffset
        );

        if (is_array($results)) {
            return $results;
        }

        return iterator_to_array($results, false);
    }

    /**
     * @param string $code
     * @param string $marker
     *
     * @return int
     */
    protected function getMarkerOffset(string $code, string $marker): int
    {
        $markerOffset = mb_strpos($code, $marker);

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
}
