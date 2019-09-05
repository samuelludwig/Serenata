<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class NamespaceListProviderTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNamespaceListForAllFiles(): void
    {
        $path = 'file://' . __DIR__ . '/NamespaceListProviderTest/';

        $this->indexTestFile($this->container, $path);

        $output = $this->container->get('namespaceListProvider')->getAll();

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
}
