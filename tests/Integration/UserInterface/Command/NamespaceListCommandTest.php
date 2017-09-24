<?php

namespace PhpIntegrator\Tests\Integration\UserInterface\Command;

use PhpIntegrator\Indexing\FileNotFoundStorageException;
use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class NamespaceListCommandTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testNamespaceList(): void
    {
        $path = __DIR__ . '/NamespaceListCommandTest/';

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('namespaceListCommand');

        $output = $command->getNamespaceList();

        static::assertCount(4, $output);
        static::assertArrayHasKey('name', $output[1]);
        static::assertSame('NamespaceA', $output[1]['name']);
        static::assertArrayHasKey('name', $output[3]);
        static::assertSame('NamespaceB', $output[3]['name']);

        $output = $command->getNamespaceList($path . 'NamespaceA.phpt');

        static::assertCount(2, $output);

        static::assertSame([
            [
                'name'      => null,
                'file'      => $path . 'NamespaceA.phpt',
                'startLine' => 0,
                'endLine'   => 2
            ],

            [
                'name'      => 'NamespaceA',
                'file'      => $path . 'NamespaceA.phpt',
                'startLine' => 3,
                'endLine'   => 9
            ]
        ], $output);
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
