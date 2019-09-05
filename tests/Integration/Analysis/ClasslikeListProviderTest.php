<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class ClasslikeListProviderTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testRetrievesAllClasses(): void
    {
        $path = __DIR__ . '/ClasslikeListProviderTest/' . 'ClassList.phpt';
        $secondPath = __DIR__ . '/ClasslikeListProviderTest/' . 'FooBarClasses.phpt';

        $this->indexTestFile($this->container, $path);
        $this->indexTestFile($this->container, $secondPath);

        $provider = $this->container->get('classlikeListProvider');

        $output = $provider->getAll();

        static::assertSame(4, count($output));
        static::assertArrayHasKey('\A\FirstClass', $output);
        static::assertArrayHasKey('\A\SecondClass', $output);
        static::assertArrayHasKey('\A\Foo', $output);
        static::assertArrayHasKey('\A\Bar', $output);
    }
}
