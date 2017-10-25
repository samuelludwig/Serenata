<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class FileIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testFileTimestampIsUpdatedOnReindexWhenContentChanges(): void
    {
        $path = $this->getPathFor('TestFile.php');

        $code = '<?php class A {}';

        $this->container->get('fileIndexer')->index($path, $code);

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);

        $timestamp = $files[0]->getIndexedOn();

        $code = '<?php class B {}';

        $this->container->get('fileIndexer')->index($path, $code);

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);
        static::assertTrue($files[0]->getIndexedOn() > $timestamp);
    }

    /**
     * @return void
     */
    public function testFileIndexIsSkippedIfSourceDidNotChange(): void
    {
        $path = $this->getPathFor('TestFile.php');

        $code = '<?php class A {}';

        $this->container->get('fileIndexer')->index($path, $code);

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);

        $timestamp = $files[0]->getIndexedOn();

        $this->container->get('fileIndexer')->index($path, $code);

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);
        static::assertEquals($files[0]->getIndexedOn(), $timestamp);
    }

    /**
     * @return void
     */
    public function testSourceHashIsUpdatedOnIndex(): void
    {
        $path = $this->getPathFor('TestFile.php');

        $code = '<?php class A {}';

        $this->container->get('fileIndexer')->index($path, $code);

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);
        static::assertNotNull($files[0]->getLastIndexedSourceHash());
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/FileIndexingTest/' . $file;
    }
}