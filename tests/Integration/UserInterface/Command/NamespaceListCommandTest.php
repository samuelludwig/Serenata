<?php

namespace PhpIntegrator\Tests\Integration\UserInterface\Command;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class NamespaceListCommandTest extends AbstractIntegrationTest
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

        $this->assertCount(4, $output);
        $this->assertArrayHasKey('name', $output[1]);
        $this->assertSame('NamespaceA', $output[1]['name']);
        $this->assertArrayHasKey('name', $output[3]);
        $this->assertSame('NamespaceB', $output[3]['name']);

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
                'endLine'   => 9
            ]
        ], $output);
    }
}
