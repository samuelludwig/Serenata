<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Contains tests that test whether the registry remains up to date (synchronized) when the state of the index changes.
 */
class NamespaceListRegistryIndexSynchronizationTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNewNamespaceIsAdded(): void
    {
        $path = $this->getPathFor('NewNamespaceIsSynchronized.phpt');

        $registry = $this->container->get('namespaceListProvider.registry');

        static::assertEmpty($registry->getAll());

        $this->indexTestFile($this->container, $path);

        $results = $registry->getAll();

        static::assertCount(2, $results);

        array_shift($results);
        $secondElement = array_shift($results);

        static::assertSame('Test', $secondElement['name']);
    }

    /**
     * @return void
     */
    public function testOldNamespaceIsRemoved(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('namespaceListProvider.registry');

            $results = $registry->getAll();

            static::assertCount(2, $results);

            array_shift($results);
            $secondElement = array_shift($results);

            static::assertSame('Test', $secondElement['name']);

            return str_replace('namespace Test', '// namespace Test ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('namespaceListProvider.registry');

            static::assertCount(1, $registry->getAll());
        };

        $path = $this->getPathFor('OldNamespaceIsRemoved.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testExistingNamespaceIsUpdated(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('namespaceListProvider.registry');

            $results = $registry->getAll();

            static::assertCount(2, $results);

            array_shift($results);
            $secondElement = array_shift($results);

            static::assertSame('Test', $secondElement['name']);
            static::assertSame(4, $secondElement['range']->getEnd()->getLine());

            return str_replace('namespace Test;', "namespace Test;\n\n", $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('namespaceListProvider.registry');

            $results = $registry->getAll();

            static::assertCount(2, $results);

            array_shift($results);
            $secondElement = array_shift($results);

            static::assertSame('Test', $secondElement['name']);
            static::assertSame(6, $secondElement['range']->getEnd()->getLine());
        };

        $path = $this->getPathFor('OldNamespaceIsRemoved.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return __DIR__ . '/NamespaceListRegistryIndexSynchronizationTest/' . $file;
    }
}
