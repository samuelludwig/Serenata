<?php

namespace Serenata\Tests\Integration\Analysis\Typing;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class FileClasslikeListProviderTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testShowsOnlyClassesForRequestedFile(): void
    {
        $path = __DIR__ . '/FileClasslikeListProviderTest/' . 'ClassList.phpt';
        $secondPath = __DIR__ . '/FileClasslikeListProviderTest/' . 'FooBarClasses.phpt';

        $this->indexTestFile($this->container, $path);
        $this->indexTestFile($this->container, $secondPath);

        $provider = $this->container->get('fileClasslikeListProvider');

        $file = $this->container->get('storage')->getFileByPath($path);

        $output = $provider->getAllForFile($file);

        static::assertSame(2, count($output));
        static::assertArrayHasKey('\A\FirstClass', $output);
        static::assertArrayHasKey('\A\SecondClass', $output);
        static::assertArrayNotHasKey('\A\Foo', $output);
        static::assertArrayNotHasKey('\A\Bar', $output);
    }
}
