<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Contains tests that test whether the registry remains up to date (synchronized) when the state of the index changes.
 */
final class NamespaceListRegistryIndexSynchronizationTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNewNamespaceIsAdded(): void
    {
        $path = $this->getPathFor('NewNamespaceIsSynchronized.phpt');

        $registry = $this->container->get('namespaceListProvider.registry');

        self::assertEmpty($registry->getAll());

        $this->indexTestFile($this->container, $path);

        $results = $registry->getAll();

        self::assertCount(2, $results);

        array_shift($results);
        $secondElement = array_shift($results);

        self::assertSame('Test', $secondElement['name']);
    }

    /**
     * @return void
     */
    public function testOldNamespaceIsRemoved(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $registry = $this->container->get('namespaceListProvider.registry');

            $results = $registry->getAll();

            self::assertCount(2, $results);

            array_shift($results);
            $secondElement = array_shift($results);

            self::assertSame('Test', $secondElement['name']);

            return str_replace('namespace Test', '// namespace Test ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $registry = $this->container->get('namespaceListProvider.registry');

            self::assertCount(1, $registry->getAll());
        };

        $path = $this->getPathFor('OldNamespaceIsRemoved.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testExistingNamespaceIsUpdated(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $registry = $this->container->get('namespaceListProvider.registry');

            $results = $registry->getAll();

            self::assertCount(2, $results);

            array_shift($results);
            $secondElement = array_shift($results);

            self::assertSame('Test', $secondElement['name']);
            self::assertSame(4, $secondElement['range']->getEnd()->getLine());

            return str_replace('namespace Test;', "namespace Test;\n\n", $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $registry = $this->container->get('namespaceListProvider.registry');

            $results = $registry->getAll();

            self::assertCount(2, $results);

            array_shift($results);
            $secondElement = array_shift($results);

            self::assertSame('Test', $secondElement['name']);
            self::assertSame(6, $secondElement['range']->getEnd()->getLine());
        };

        $path = $this->getPathFor('OldNamespaceIsRemoved.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/NamespaceListRegistryIndexSynchronizationTest/' . $file;
    }
}
