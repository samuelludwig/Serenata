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

        self::assertSame(4, count($output));
        self::assertArrayHasKey('\A\FirstClass', $output);
        self::assertArrayHasKey('\A\SecondClass', $output);
        self::assertArrayHasKey('\A\Foo', $output);
        self::assertArrayHasKey('\A\Bar', $output);
    }
}
