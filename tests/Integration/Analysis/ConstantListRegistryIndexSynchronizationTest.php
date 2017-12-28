<?php

namespace PhpIntegrator\Tests\Integration\Analysis;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Contains tests that test whether the registry remains up to date (synchronized) when the state of the index changes.
 */
class ConstantListRegistryIndexSynchronizationTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNewConstantIsAdded(): void
    {
        $path = $this->getPathFor('NewConstantIsSynchronized.phpt');

        $registry = $this->container->get('constantListProvider.registry');

        static::assertEmpty($registry->getAll());

        $this->indexTestFile($this->container, $path);

        static::assertCount(1, $registry->getAll());
        static::assertArrayHasKey('\TEST', $registry->getAll());
    }

    /**
     * @return void
     */
    public function testOldConstantIsRemoved(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('constantListProvider.registry');

            static::assertCount(1, $registry->getAll());
            static::assertArrayHasKey('\TEST', $registry->getAll());

            return str_replace('const TEST', '// const TEST ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('constantListProvider.registry');

            static::assertEmpty($registry->getAll());
        };

        $path = $this->getPathFor('OldConstantIsRemoved.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testExistingConstantIsUpdated(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('constantListProvider.registry');

            static::assertCount(1, $registry->getAll());
            static::assertArrayHasKey('\TEST', $registry->getAll());
            static::assertSame('1', $registry->getAll()['\TEST']['defaultValue']);

            return str_replace('const TEST = 1', 'const TEST = 2', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('constantListProvider.registry');

            static::assertCount(1, $registry->getAll());
            static::assertArrayHasKey('\TEST', $registry->getAll());
            static::assertSame('2', $registry->getAll()['\TEST']['defaultValue']);
        };

        $path = $this->getPathFor('OldConstantIsRemoved.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/ConstantListRegistryIndexSynchronizationTest/' . $file;
    }
}
