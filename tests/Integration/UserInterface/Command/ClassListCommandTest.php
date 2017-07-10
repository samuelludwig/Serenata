<?php

namespace PhpIntegrator\Tests\Integration\UserInterface\Command;

use PhpIntegrator\Indexing\FileNotFoundStorageException;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

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

        $this->assertArrayHasKey('\N\SimpleClass', $command->getAll());
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
    //     $this->assertEmpty($command->getAll());
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
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/ClassListCommandTest/' . $file;
    }
}
