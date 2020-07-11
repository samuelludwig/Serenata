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

        self::assertEmpty($registry->getAll());

        $this->indexTestFile($this->container, $path);

        self::assertCount(1, $registry->getAll());
        self::assertArrayHasKey('\test', $registry->getAll());
    }

    /**
     * @return void
     */
    public function testOldFunctionIsRemoved(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $registry = $this->container->get('functionListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\test', $registry->getAll());

            return str_replace('function test', '// function test ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $registry = $this->container->get('functionListProvider.registry');

            self::assertEmpty($registry->getAll());
        };

        $path = $this->getPathFor('OldFunctionIsRemoved.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testExistingFunctionIsUpdated(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $registry = $this->container->get('functionListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\test', $registry->getAll());
            self::assertEmpty($registry->getAll()['\test']['parameters']);

            return str_replace('function test()', 'function test(int $a) ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $registry = $this->container->get('functionListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\test', $registry->getAll());
            self::assertCount(1, $registry->getAll()['\test']['parameters']);
        };

        $path = $this->getPathFor('OldFunctionIsRemoved.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
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
