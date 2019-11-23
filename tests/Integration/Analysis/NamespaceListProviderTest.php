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

        $namespaceAItem = array_filter($output, function (array $item) {
            return $item['name'] === 'NamespaceA';
        });

        static::assertNotEmpty($namespaceAItem);

        $namespaceBItem = array_filter($output, function (array $item) {
            return $item['name'] === 'NamespaceB';
        });

        static::assertNotEmpty($namespaceBItem);
    }
}
