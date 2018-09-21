<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class ClassListCommandTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleClass(): void
    {
        $path = $this->getPathFor('SimpleClass.phpt');

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('classListCommand');

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
    //     $command = $this->container->get('classListCommand');
    //
    //     static::assertEmpty($command->getAll());
    // }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('classListCommand');

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
        return 'file:///' . __DIR__ . '/ClassListCommandTest/' . $file;
    }
}
