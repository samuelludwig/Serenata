<?php

namespace PhpIntegrator\Tests\Integration\Analysis;

use PhpIntegrator\Analysis\ClassListProvider;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassListProviderTest extends AbstractIntegrationTest
{
    /**
     * @return ClassListProvider
     */
    protected function createClassListProvider(ContainerBuilder $container): ClassListProvider
    {
        return new ClassListProvider(
            $container->get('classlikeConverter'),
            $container->get('indexDatabase')
        );
    }

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

        $provider = $this->createClassListProvider($container);

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

        $provider = $this->createClassListProvider($container);

        $output = $provider->getAllForFile($path);

        $this->assertEquals(2, count($output));
        $this->assertArrayHasKey('\A\FirstClass', $output);
        $this->assertArrayHasKey('\A\SecondClass', $output);
        $this->assertArrayNotHasKey('\A\Foo', $output);
        $this->assertArrayNotHasKey('\A\Bar', $output);
    }
}
