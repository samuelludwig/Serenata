<?php

namespace PhpIntegrator\Tests\Integration\UserInterface\Command;

use PhpIntegrator\Tests\AbstractIndexedTest;

class NamespaceListCommandTest extends AbstractIndexedTest
{
    /**
     * @return void
     */
    public function testNamespaceList(): void
    {
        $path = __DIR__ . '/NamespaceListCommandTest/';

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);

        $command = $container->get('namespaceListCommand');

        $output = $command->getNamespaceList();

        $this->assertCount(2, $output);
        $this->assertArrayHasKey('namespace', $output[0]);
        $this->assertSame('NamespaceA', $output[0]['namespace']);
        $this->assertArrayHasKey('namespace', $output[1]);
        $this->assertSame('NamespaceB', $output[1]['namespace']);

        $output = $command->getNamespaceList($path . 'NamespaceA.phpt');

        $this->assertCount(2, $output);

        $this->assertEquals([
            [
                'name'      => null,
                'startLine' => 0,
                'endLine'   => 2
            ],

            [
                'name'      => 'NamespaceA',
                'startLine' => 3,
                'endLine'   => null
            ]
        ], $output);
    }
}
