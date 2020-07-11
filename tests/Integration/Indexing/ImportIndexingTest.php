<?php

namespace Serenata\Tests\Integration\Indexing;

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

        self::assertCount(1, $namespaces);
        self::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 8)
            ),
            $import->getRange()
        );

        self::assertSame('A', $import->getAlias());
        self::assertSame('N\A', $import->getName());
        self::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        self::assertSame($namespaces[0], $import->getNamespace());
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

        self::assertCount(1, $namespaces);
        self::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 13)
            ),
            $import->getRange()
        );

        self::assertSame('B', $import->getAlias());
        self::assertSame('N\A', $import->getName());
        self::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        self::assertSame($namespaces[0], $import->getNamespace());
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

        self::assertCount(1, $namespaces);
        self::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 17)
            ),
            $import->getRange()
        );

        self::assertSame('A', $import->getAlias());
        self::assertSame('N\A', $import->getName());
        self::assertSame(UseStatementKind::TYPE_FUNCTION, $import->getKind());
        self::assertSame($namespaces[0], $import->getNamespace());
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

        self::assertCount(1, $namespaces);
        self::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 14)
            ),
            $import->getRange()
        );

        self::assertSame('A', $import->getAlias());
        self::assertSame('N\A', $import->getName());
        self::assertSame(UseStatementKind::TYPE_CONSTANT, $import->getKind());
        self::assertSame($namespaces[0], $import->getNamespace());
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

        self::assertCount(1, $namespaces);
        self::assertCount(2, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(5, 1)
            ),
            $import->getRange()
        );

        self::assertSame('A', $import->getAlias());
        self::assertSame('N\A', $import->getName());
        self::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        self::assertSame($namespaces[0], $import->getNamespace());

        $import = $namespaces[0]->getImports()[1];

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(5, 1)
            ),
            $import->getRange()
        );

        self::assertSame('C', $import->getAlias());
        self::assertSame('N\B', $import->getName());
        self::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        self::assertSame($namespaces[0], $import->getNamespace());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $file = $container->get('storage')->getFileByUri($path);

            self::assertCount(1, $file->getNamespaces());
            self::assertCount(1, $file->getNamespaces()[0]->getImports());
            self::assertSame('N\A', $file->getNamespaces()[0]->getImports()[0]->getName());

            return str_replace('N\A', 'N\B', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $file = $container->get('storage')->getFileByUri($path);

            self::assertCount(1, $file->getNamespaces());
            self::assertCount(1, $file->getNamespaces()[0]->getImports());
            self::assertSame('N\B', $file->getNamespaces()[0]->getImports()[0]->getName());
        };

        $path = $this->getPathFor('ImportChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
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
