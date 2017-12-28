<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class FilePruningTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testPruneRemovedFiles(): void
    {
        $path = __DIR__ . '/FilePruningTest';

        $testFilePath = $path . '/file.php';

        file_put_contents($testFilePath, '<?php class A {}');

        static::assertTrue(file_exists($testFilePath), 'Could not create test file');

        $this->indexPath($this->container, $path);

        $files = $this->container->get('storage')->getFiles();

        unlink($testFilePath);

        static::assertCount(1, $files);
        static::assertSame($testFilePath, $files[0]->getPath());

        $this->container->get('indexFilePruner')->prune();

        $files = $this->container->get('storage')->getFiles();

        static::assertEmpty($files);
    }
}
