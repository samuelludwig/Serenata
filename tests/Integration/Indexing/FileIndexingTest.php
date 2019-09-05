<?php

namespace Serenata\Tests\Integration\Tooltips;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Utility\TextDocumentItem;

final class FileIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testFileTimestampIsUpdatedOnReindexWhenContentChanges(): void
    {
        $path = $this->getPathFor('TestFile.phpt');

        $code = '<?php class A {}';

        $this->container->get('fileIndexer')->index(new TextDocumentItem($path, $code));

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);

        $timestamp = $files[0]->getIndexedOn();

        $code = '<?php class B {}';

        $this->container->get('fileIndexer')->index(new TextDocumentItem($path, $code));

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);
        static::assertTrue($files[0]->getIndexedOn() > $timestamp);
    }

    /**
     * @return void
     */
    public function testFileIndexIsSkippedIfSourceDidNotChange(): void
    {
        $path = $this->getPathFor('TestFile.phpt');

        $code = '<?php class A {}';

        $this->container->get('fileIndexer')->index(new TextDocumentItem($path, $code));

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);

        $timestamp = $files[0]->getIndexedOn();

        $this->container->get('fileIndexer')->index(new TextDocumentItem($path, $code));

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);
        static::assertTrue($files[0]->getIndexedOn() > $timestamp);
    }

    /**
     * @return void
     */
    public function testSourceHashIsUpdatedOnIndex(): void
    {
        $path = $this->getPathFor('TestFile.phpt');

        $code = '<?php class A {}';

        $this->container->get('fileIndexer')->index(new TextDocumentItem($path, $code));

        $files = $this->container->get('storage')->getFiles();

        static::assertCount(1, $files);
        static::assertNotNull($files[0]->getLastIndexedSourceHash());
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/FileIndexingTest/' . $file;
    }
}
