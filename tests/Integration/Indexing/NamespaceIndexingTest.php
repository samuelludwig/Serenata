<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class NamespaceIndexingTest extends AbstractIntegrationTest
{
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
     * @return ContainerBuilder
     */
    protected function index(string $file): ContainerBuilder
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        return $this->container;
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
