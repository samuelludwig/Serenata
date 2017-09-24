<?php

namespace PhpIntegrator\Tests\Integration\Analysis;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class StructureListProviderTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testRetrievesAllClasses(): void
    {
        $path = __DIR__ . '/StructureListProviderTest/' . 'ClassList.phpt';
        $secondPath = __DIR__ . '/StructureListProviderTest/' . 'FooBarClasses.phpt';

        $this->indexTestFile($this->container, $path);
        $this->indexTestFile($this->container, $secondPath);

        $provider = $this->container->get('structureListProvider');

        $output = $provider->getAll();

        static::assertSame(4, count($output));
        static::assertArrayHasKey('\A\FirstClass', $output);
        static::assertArrayHasKey('\A\SecondClass', $output);
        static::assertArrayHasKey('\A\Foo', $output);
        static::assertArrayHasKey('\A\Bar', $output);
    }
}
