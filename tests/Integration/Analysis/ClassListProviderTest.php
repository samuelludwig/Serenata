<?php

namespace PhpIntegrator\Tests\Integration\Analysis;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class ClassListProviderTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testRetrievesAllClasses(): void
    {
        $path = __DIR__ . '/ClassListProviderTest/' . 'ClassList.phpt';
        $secondPath = __DIR__ . '/ClassListProviderTest/' . 'FooBarClasses.phpt';

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);
        $this->indexTestFile($container, $secondPath);

        $provider = $container->get('doctrineClassListProvider');

        $output = $provider->getAll();

        $this->assertEquals(4, count($output));
        $this->assertArrayHasKey('\A\FirstClass', $output);
        $this->assertArrayHasKey('\A\SecondClass', $output);
        $this->assertArrayHasKey('\A\Foo', $output);
        $this->assertArrayHasKey('\A\Bar', $output);
    }

    /**
     * @return void
     */
    public function testShowsOnlyClassesForRequestedFile(): void
    {
        $path = __DIR__ . '/ClassListProviderTest/' . 'ClassList.phpt';
        $secondPath = __DIR__ . '/ClassListProviderTest/' . 'FooBarClasses.phpt';

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);
        $this->indexTestFile($container, $secondPath);

        $provider = $container->get('doctrineClassListProvider');

        $output = $provider->getAllForFile($path);

        $this->assertEquals(2, count($output));
        $this->assertArrayHasKey('\A\FirstClass', $output);
        $this->assertArrayHasKey('\A\SecondClass', $output);
        $this->assertArrayNotHasKey('\A\Foo', $output);
        $this->assertArrayNotHasKey('\A\Bar', $output);
    }
}
