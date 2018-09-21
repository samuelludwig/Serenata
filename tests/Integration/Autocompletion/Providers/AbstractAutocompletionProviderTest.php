<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\Providers\AutocompletionProviderContext;

use Serenata\Common\Position;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

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
     *
     * @return string[]
     */
    protected function provide(string $file, ?string $additionalFile = null): array
    {
        $path = $this->getPathFor($file);

        $container = $this->createTestContainer();

        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        if ($additionalFile !== null) {
            $additionalCode = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile(
                $this->getPathFor($additionalFile)
            );

            $code = str_replace('// <INJECTION>', $additionalCode, $code, $count);

            static::assertGreaterThan(0, $count, 'Injection point for additional code not found in file ' . $file);
        }

        $markerString = '// <MARKER>';

        $markerOffset = $this->getMarkerOffset($code, $markerString);

        // Strip marker so it does not influence further processing.
        $code = str_replace($markerString, '', $code);

        $this->indexTestFileWithSource($container, $path, $code);

        $provider = $container->get($this->getProviderName());
        $position = Position::createFromByteOffset($markerOffset, $code, PositionEncoding::VALUE);

        $results = $provider->provide(new AutocompletionProviderContext(
            new TextDocumentItem($path, $code),
            $position,
            $container->get('defaultAutocompletionPrefixDeterminer')->determine($code, $position)
        ));

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
        return 'file:///' . __DIR__ . '/' . $this->getFolderName() . '/' . $fileName;
    }
}
