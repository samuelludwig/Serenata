<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Contains tests that test whether the registry remains up to date (synchronized) when the state of the index changes.
 */
final class ClasslikeListRegistryIndexSynchronizationTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNewStructureIsAdded(): void
    {
        $path = $this->getPathFor('NewStructureIsSynchronized.phpt');

        $registry = $this->container->get('classlikeListProvider.registry');

        self::assertEmpty($registry->getAll());

        $this->indexTestFile($this->container, $path);

        self::assertCount(1, $registry->getAll());
        self::assertArrayHasKey('\Test', $registry->getAll());
    }

    /**
     * @return void
     */
    public function testOldStructureIsRemoved(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('classlikeListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\Test', $registry->getAll());

            return str_replace('class Test', '// class Test ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('classlikeListProvider.registry');

            self::assertEmpty($registry->getAll());
        };

        $path = $this->getPathFor('OldStructureIsRemoved.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testExistingStructureIsUpdated(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('classlikeListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\Test', $registry->getAll());
            self::assertFalse($registry->getAll()['\Test']['isFinal']);

            return str_replace('class Test {}', 'final class Test {}', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $registry = $this->container->get('classlikeListProvider.registry');

            self::assertCount(1, $registry->getAll());
            self::assertArrayHasKey('\Test', $registry->getAll());
            self::assertTrue($registry->getAll()['\Test']['isFinal']);
        };

        $path = $this->getPathFor('OldStructureIsRemoved.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/ClasslikeListRegistryIndexSynchronizationTest/' . $file;
    }
}
