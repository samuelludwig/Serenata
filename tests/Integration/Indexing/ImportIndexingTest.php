<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ImportIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNormalImport(): void
    {
        $path = $this->getPathFor('NormalImport.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByPath($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertSame(3, $import->getLine());
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

        $file = $this->container->get('storage')->getFileByPath($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertSame(3, $import->getLine());
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

        $file = $this->container->get('storage')->getFileByPath($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertSame(3, $import->getLine());
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

        $file = $this->container->get('storage')->getFileByPath($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(1, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertSame(3, $import->getLine());
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

        $file = $this->container->get('storage')->getFileByPath($path);

        $namespaces = $file->getNamespaces();

        static::assertCount(1, $namespaces);
        static::assertCount(2, $namespaces[0]->getImports());

        $import = $namespaces[0]->getImports()[0];

        static::assertSame(3, $import->getLine());
        static::assertSame('A', $import->getAlias());
        static::assertSame('N\A', $import->getName());
        static::assertSame(UseStatementKind::TYPE_CLASSLIKE, $import->getKind());
        static::assertSame($namespaces[0], $import->getNamespace());

        $import = $namespaces[0]->getImports()[1];

        static::assertSame(3, $import->getLine());
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
            $file = $container->get('storage')->getFileByPath($path);

            static::assertCount(1, $file->getNamespaces());
            static::assertCount(1, $file->getNamespaces()[0]->getImports());
            static::assertSame('N\A', $file->getNamespaces()[0]->getImports()[0]->getName());

            return str_replace('N\A', 'N\B', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->getFileByPath($path);

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
        return __DIR__ . '/ImportIndexingTest/' . $file;
    }
}
