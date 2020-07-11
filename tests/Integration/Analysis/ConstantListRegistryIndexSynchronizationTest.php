<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Contains tests that test whether the registry remains up to date (synchronized) when the state of the index changes.
 */
final class ConstantListRegistryIndexSynchronizationTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNewConstantIsAdded(): void
    {
        $path = $this->getPathFor('NewConstantIsSynchronized.phpt');

        $registry = $this->container->get('constantListProvider.registry');

        self::assertEmpty($registry->getAll());

        $this->indexTestFile($this->container, $path);

        self::assertCount(1, $registry->getAll());
        self::assertArrayHasKey('\TEST', $registry->getAll());
    }

    /**
     * @return void
     */
    public function testOldConstantIsRemoved(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('constantListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\TEST', $registry->getAll());

            return str_replace('const TEST', '// const TEST ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('constantListProvider.registry');

            self::assertEmpty($registry->getAll());
        };

        $path = $this->getPathFor('OldConstantIsRemoved.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testExistingConstantIsUpdated(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('constantListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\TEST', $registry->getAll());
            self::assertSame('1', $registry->getAll()['\TEST']['defaultValue']);

            return str_replace('const TEST = 1', 'const TEST = 2', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('constantListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\TEST', $registry->getAll());
            self::assertSame('2', $registry->getAll()['\TEST']['defaultValue']);
        };

        $path = $this->getPathFor('OldConstantIsRemoved.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/ConstantListRegistryIndexSynchronizationTest/' . $file;
    }
}
