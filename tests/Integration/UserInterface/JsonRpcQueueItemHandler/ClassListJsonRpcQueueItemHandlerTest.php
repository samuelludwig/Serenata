<?php

namespace Serenata\Tests\Integration\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class ClassListJsonRpcQueueItemHandlerTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleClass(): void
    {
        $path = $this->getPathFor('SimpleClass.phpt');

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('classListJsonRpcQueueItemHandler');

        static::assertArrayHasKey('\N\SimpleClass', $command->getAll());
    }

    /**
     * @return void
     */
    // public function testAnonymousClassIsExcluded(): void
    // {
    //     $path = $this->getPathFor('AnonymousClassIsExcluded.phpt');
    //
    //     $this->indexTestFile($this->container, $path);
    //
    //     $command = $this->container->get('classListJsonRpcQueueItemHandler');
    //
    //     static::assertEmpty($command->getAll());
    // }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('classListJsonRpcQueueItemHandler');

        $this->expectException(FileNotFoundStorageException::class);

        $command->getAllForFilePath('DoesNotExist.phpt');
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/ClassListJsonRpcQueueItemHandlerTest/' . $file;
    }
}
