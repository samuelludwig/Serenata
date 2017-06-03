<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class NamespaceIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testIsCorrectlyIndexed(): void
    {
        $path = $this->getPathFor('Namespace.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->findFileByPath($path);

        $namespaces = $file->getNamespaces();

        $this->assertCount(3, $namespaces);

        $this->assertEquals(0, $namespaces[0]->getStartLine());
        $this->assertEquals(2, $namespaces[0]->getEndLine());
        $this->assertEquals(null, $namespaces[0]->getName());
        $this->assertEquals($path, $namespaces[0]->getFile()->getPath());
        $this->assertEmpty($namespaces[0]->getImports());

        $this->assertEquals(3, $namespaces[1]->getStartLine());
        $this->assertEquals(6, $namespaces[1]->getEndLine());
        $this->assertEquals('N', $namespaces[1]->getName());
        $this->assertEquals($path, $namespaces[1]->getFile()->getPath());
        $this->assertEmpty($namespaces[1]->getImports());

        $this->assertEquals(7, $namespaces[2]->getStartLine());
        $this->assertEquals(10, $namespaces[2]->getEndLine());
        $this->assertEquals(null, $namespaces[2]->getName());
        $this->assertEquals($path, $namespaces[2]->getFile()->getPath());
        $this->assertCount(1, $namespaces[2]->getImports());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->findFileByPath($path);

            $this->assertCount(3, $file->getNamespaces());
            $this->assertEquals('N', $file->getNamespaces()[1]->getName());

            return str_replace('namespace N', 'namespace ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $file = $container->get('storage')->findFileByPath($path);

            $this->assertCount(3, $file->getNamespaces());
            $this->assertEquals(null, $file->getNamespaces()[1]->getName());
        };

        $path = $this->getPathFor('Namespace.phpt');

        $this->assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/NamespaceIndexingTest/' . $file;
    }
}
