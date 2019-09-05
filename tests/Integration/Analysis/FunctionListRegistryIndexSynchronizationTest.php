<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Contains tests that test whether the registry remains up to date (synchronized) when the state of the index changes.
 */
final class FunctionListRegistryIndexSynchronizationTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNewFunctionIsAdded(): void
    {
        $path = $this->getPathFor('NewFunctionIsSynchronized.phpt');

        $registry = $this->container->get('functionListProvider.registry');

        static::assertEmpty($registry->getAll());

        $this->indexTestFile($this->container, $path);

        static::assertCount(1, $registry->getAll());
        static::assertArrayHasKey('\test', $registry->getAll());
    }

    /**
     * @return void
     */
    public function testOldFunctionIsRemoved(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('functionListProvider.registry');

            static::assertCount(1, $registry->getAll());
            static::assertArrayHasKey('\test', $registry->getAll());

            return str_replace('function test', '// function test ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('functionListProvider.registry');

            static::assertEmpty($registry->getAll());
        };

        $path = $this->getPathFor('OldFunctionIsRemoved.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testExistingFunctionIsUpdated(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('functionListProvider.registry');

            static::assertCount(1, $registry->getAll());
            static::assertArrayHasKey('\test', $registry->getAll());
            static::assertEmpty($registry->getAll()['\test']['parameters']);

            return str_replace('function test()', 'function test(int $a) ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('functionListProvider.registry');

            static::assertCount(1, $registry->getAll());
            static::assertArrayHasKey('\test', $registry->getAll());
            static::assertCount(1, $registry->getAll()['\test']['parameters']);
        };

        $path = $this->getPathFor('OldFunctionIsRemoved.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/FunctionListRegistryIndexSynchronizationTest/' . $file;
    }
}
