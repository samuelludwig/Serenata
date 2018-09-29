<?php

namespace Serenata\Tests\Integration\Tooltips;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class NamespaceIndexingTest extends AbstractIntegrationTest
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

        static::assertCount(1, $namespaces);

        static::assertEquals(
            new Range(
                new Position(0, 0),
                new Position(2, 0)
            ),
            $namespaces[0]->getRange()
        );

        static::assertSame(null, $namespaces[0]->getName());
        static::assertSame($path, $namespaces[0]->getFile()->getUri());
        static::assertEmpty($namespaces[0]->getImports());
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

        static::assertCount(2, $namespaces);

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(6, 0)
            ),
            $namespaces[1]->getRange()
        );

        static::assertSame('N', $namespaces[1]->getName());
        static::assertSame($path, $namespaces[1]->getFile()->getUri());
        static::assertEmpty($namespaces[1]->getImports());
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

        static::assertCount(2, $namespaces);

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(6, 0)
            ),
            $namespaces[1]->getRange()
        );

        static::assertSame(null, $namespaces[1]->getName());
        static::assertSame($path, $namespaces[1]->getFile()->getUri());
        static::assertCount(1, $namespaces[1]->getImports());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->getFileByUri($path);

            static::assertCount(3, $file->getNamespaces());
            static::assertSame('N', $file->getNamespaces()[1]->getName());

            return str_replace('namespace N', 'namespace ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->getFileByUri($path);

            static::assertCount(3, $file->getNamespaces());
            static::assertSame(null, $file->getNamespaces()[1]->getName());
        };

        $path = $this->getPathFor('NamespaceChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
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
