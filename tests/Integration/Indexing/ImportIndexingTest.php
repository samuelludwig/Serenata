<?php

namespace Serenata\Tests\Integration\Tooltips;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ImportIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNormalImport(): void
    {
        $path = $this->getPathFor('NormalImport.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 8)
            ),
            $import->getRange()
        );

        static::assertSame('A', $import->getAlias());
        static::assertSame('N\A', $import->getName());
        static::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        static::assertSame($namespaces[0], $import->getNamespace());
    }

    /**
     * @return void
     */
    public function testAliasedImport(): void
    {
        $path = $this->getPathFor('AliasedImport.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 13)
            ),
            $import->getRange()
        );

        static::assertSame('B', $import->getAlias());
        static::assertSame('N\A', $import->getName());
        static::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        static::assertSame($namespaces[0], $import->getNamespace());
    }

    /**
     * @return void
     */
    public function testFunctionImport(): void
    {
        $path = $this->getPathFor('FunctionImport.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 17)
            ),
            $import->getRange()
        );

        static::assertSame('A', $import->getAlias());
        static::assertSame('N\A', $import->getName());
        static::assertSame(UseStatementKind::TYPE_FUNCTION, $import->getKind());
        static::assertSame($namespaces[0], $import->getNamespace());
    }

    /**
     * @return void
     */
    public function testConstantImport(): void
    {
        $path = $this->getPathFor('ConstantImport.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 14)
            ),
            $import->getRange()
        );

        static::assertSame('A', $import->getAlias());
        static::assertSame('N\A', $import->getName());
        static::assertSame(UseStatementKind::TYPE_CONSTANT, $import->getKind());
        static::assertSame($namespaces[0], $import->getNamespace());
    }

    /**
     * @return void
     */
    public function testGroupedImport(): void
    {
        $path = $this->getPathFor('GroupedImport.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(2, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(5, 1)
            ),
            $import->getRange()
        );

        static::assertSame('A', $import->getAlias());
        static::assertSame('N\A', $import->getName());
        static::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        static::assertSame($namespaces[0], $import->getNamespace());

        $import = $namespaces[0]->getImports()[1];

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(5, 1)
            ),
            $import->getRange()
        );

        static::assertSame('C', $import->getAlias());
        static::assertSame('N\B', $import->getName());
        static::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        static::assertSame($namespaces[0], $import->getNamespace());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->getFileByUri($path);

            static::assertCount(1, $file->getNamespaces());
            static::assertCount(1, $file->getNamespaces()[0]->getImports());
            static::assertSame('N\A', $file->getNamespaces()[0]->getImports()[0]->getName());

            return str_replace('N\A', 'N\B', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->getFileByUri($path);

            static::assertCount(1, $file->getNamespaces());
            static::assertCount(1, $file->getNamespaces()[0]->getImports());
            static::assertSame('N\B', $file->getNamespaces()[0]->getImports()[0]->getName());
        };

        $path = $this->getPathFor('ImportChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/ImportIndexingTest/' . $file;
    }
}
