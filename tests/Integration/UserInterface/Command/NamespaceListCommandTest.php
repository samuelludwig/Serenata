<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Indexing\FileNotFoundStorageException;
use Serenata\Tests\Integration\AbstractIntegrationTest;

class NamespaceListCommandTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNamespaceListForAllFiles(): void
    {
        $path = __DIR__ . '/NamespaceListCommandTest/';

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('namespaceListCommand');

        $output = $command->getNamespaceList();

        static::assertCount(4, $output);

        array_shift($output);
        $secondItem = array_shift($output);

        static::assertArrayHasKey('name', $secondItem);
        static::assertSame('NamespaceA', $secondItem['name']);

        array_shift($output);
        $fourthItem = array_shift($output);

        static::assertArrayHasKey('name', $fourthItem);
        static::assertSame('NamespaceB', $fourthItem['name']);
    }

    /**
     * @return void
     */
    public function testNamespaceListForSpecificFile(): void
    {
        $path = __DIR__ . '/NamespaceListCommandTest/';

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('namespaceListCommand');

        $output = $command->getNamespaceList($path . 'NamespaceA.phpt');

        static::assertCount(2, $output);

        $firstItem = array_shift($output);

        static::assertSame(null, $firstItem['name']);
        static::assertSame($path . 'NamespaceA.phpt', $firstItem['file']);
        static::assertSame(0, $firstItem['startLine']);
        static::assertSame(2, $firstItem['endLine']);

        $secondItem = array_shift($output);

        static::assertSame('NamespaceA', $secondItem['name']);
        static::assertSame($path . 'NamespaceA.phpt', $secondItem['file']);
        static::assertSame(3, $secondItem['startLine']);
        static::assertSame(9, $secondItem['endLine']);
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('namespaceListCommand');

        $this->expectException(FileNotFoundStorageException::class);

        $command->getNamespaceList('DoesNotExist.phpt');
    }
}
