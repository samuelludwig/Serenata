<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests file indexing in combination with the file namespace provider.
 *
 * The file namespace provider performs caching, so these integration tests ensure that the cache is properly cleared
 * when the source changes.
 */
final class FileIndexerFileNamespaceProviderCombinationTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNewImportsArePickedUpIn(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $results = $container->get('fileNamespaceProvider')->provide($path);

            self::assertCount(3, $results);
            self::assertEmpty($results[2]->getImports());

            return str_replace('// ', '', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $results = $container->get('fileNamespaceProvider')->provide($path);

            self::assertCount(3, $results);
            self::assertCount(1, $results[2]->getImports(), 'Failed asserting that file namespace provider picks up new imports after reindex');
        };

        $path = $this->getPathFor('NewImportClearsCache.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/FileIndexerFileNamespaceProviderCombinationTest/' . $file;
    }
}
