<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Typing;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class FileStructureListProviderTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testShowsOnlyClassesForRequestedFile(): void
    {
        $path = __DIR__ . '/FileStructureListProviderTest/' . 'ClassList.phpt';
        $secondPath = __DIR__ . '/FileStructureListProviderTest/' . 'FooBarClasses.phpt';

        $this->indexTestFile($this->container, $path);
        $this->indexTestFile($this->container, $secondPath);

        $provider = $this->container->get('fileStructureListProvider');

        $file = $this->container->get('storage')->getFileByPath($path);

        $output = $provider->getAllForFile($file);

        static::assertSame(2, count($output));
        static::assertArrayHasKey('\A\FirstClass', $output);
        static::assertArrayHasKey('\A\SecondClass', $output);
        static::assertArrayNotHasKey('\A\Foo', $output);
        static::assertArrayNotHasKey('\A\Bar', $output);
    }
}
