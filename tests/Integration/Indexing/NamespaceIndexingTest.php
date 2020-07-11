<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NamespaceIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testPermanentStartNamespace(): void
    {
        $path = $this->getPathFor('PermanentStartNamespace.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path);

        $namespaces = $file->getNamespaces();

        self::assertCount(1, $namespaces);

        self::assertEquals(
            new Range(
                new Position(0, 0),
                new Position(2, 0)
            ),
            $namespaces[0]->getRange()
        );

        self::assertSame(null, $namespaces[0]->getName());
        self::assertSame($this->normalizePath($path), $namespaces[0]->getFile()->getUri());
        self::assertEmpty($namespaces[0]->getImports());
    }

    /**
     * @return void
     */
    public function testNormalNamespace(): void
    {
        $path = $this->getPathFor('NormalNamespace.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path);

        $namespaces = $file->getNamespaces();

        self::assertCount(2, $namespaces);

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(6, 0)
            ),
            $namespaces[1]->getRange()
        );

        self::assertSame('N', $namespaces[1]->getName());
        self::assertSame($this->normalizePath($path), $namespaces[1]->getFile()->getUri());
        self::assertEmpty($namespaces[1]->getImports());
    }

    /**
     * @return void
     */
    public function testAnonymousNamespace(): void
    {
        $path = $this->getPathFor('AnonymousNamespace.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path);

        $namespaces = $file->getNamespaces();

        self::assertCount(2, $namespaces);

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(6, 0)
            ),
            $namespaces[1]->getRange()
        );

        self::assertSame(null, $namespaces[1]->getName());
        self::assertSame($this->normalizePath($path), $namespaces[1]->getFile()->getUri());
        self::assertCount(1, $namespaces[1]->getImports());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->getFileByUri($path);

            self::assertCount(3, $file->getNamespaces());
            self::assertSame('N', $file->getNamespaces()[1]->getName());

            return str_replace('namespace N', 'namespace ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->getFileByUri($path);

            self::assertCount(3, $file->getNamespaces());
            self::assertSame(null, $file->getNamespaces()[1]->getName());
        };

        $path = $this->getPathFor('NamespaceChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/NamespaceIndexingTest/' . $file;
    }
}
