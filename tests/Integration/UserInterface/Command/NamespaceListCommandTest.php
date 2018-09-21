<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Common\Range;
use Serenata\Common\Position;

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
        static::assertSame($path . 'NamespaceA.phpt', $firstItem['uri']);

        static::assertEquals(
            new Range(
                new Position(0, 0),
                new Position(2, 0)
            ),
            $firstItem['range']
        );

        $secondItem = array_shift($output);

        static::assertSame('NamespaceA', $secondItem['name']);
        static::assertSame($path . 'NamespaceA.phpt', $secondItem['uri']);

        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(9, 0)
            ),
            $secondItem['range']
        );
    }
}
