<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class FileNamespaceListProviderTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNamespaceListForSpecificFile(): void
    {
        $path = 'file://' . __DIR__ . '/FileNamespaceListProviderTest/';
        $normalized = $this->normalizePath($path . 'NamespaceA.phpt');

        $this->indexTestFile($this->container, $path);

        $file = $this->container->get('storage')->getFileByUri($path . 'NamespaceA.phpt');

        $output = $this->container->get('fileNamespaceListProvider')->getAllForFile($file);

        self::assertCount(2, $output);

        $firstItem = array_shift($output);

        self::assertSame(null, $firstItem['name']);
        self::assertSame($normalized, $firstItem['uri']);

        self::assertEquals(
            new Range(
                new Position(0, 0),
                new Position(2, 0)
            ),
            $firstItem['range']
        );

        $secondItem = array_shift($output);

        self::assertSame('NamespaceA', $secondItem['name']);
        self::assertSame($normalized, $secondItem['uri']);

        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(9, 0)
            ),
            $secondItem['range']
        );
    }
}
