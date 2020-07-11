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

        self::assertCount(4, $output);

        $namespaceAItem = array_filter($output, function (array $item): bool {
            return $item['name'] === 'NamespaceA';
        });

        self::assertNotEmpty($namespaceAItem);

        $namespaceBItem = array_filter($output, function (array $item): bool {
            return $item['name'] === 'NamespaceB';
        });

        self::assertNotEmpty($namespaceBItem);
    }
}
