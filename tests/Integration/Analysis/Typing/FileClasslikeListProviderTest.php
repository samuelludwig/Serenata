<?php

namespace Serenata\Tests\Integration\Analysis\Typing;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class FileClasslikeListProviderTest extends AbstractIntegrationTest
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

        $file = $this->container->get('storage')->getFileByUri($path);

        $output = $provider->getAllForFile($file);

        self::assertSame(2, count($output));
        self::assertArrayHasKey('\A\FirstClass', $output);
        self::assertArrayHasKey('\A\SecondClass', $output);
        self::assertArrayNotHasKey('\A\Foo', $output);
        self::assertArrayNotHasKey('\A\Bar', $output);
    }
}
