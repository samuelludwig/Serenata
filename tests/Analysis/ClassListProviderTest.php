<?php

namespace PhpIntegrator\Tests\Analysis;

use PhpIntegrator\Analysis\ClassListProvider;

use PhpIntegrator\Tests\IndexedTest;

class ClassListProviderTest extends IndexedTest
{
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

        $provider = new ClassListProvider(
            $container->get('constantConverter'),
            $container->get('classlikeConstantConverter'),
            $container->get('propertyConverter'),
            $container->get('functionConverter'),
            $container->get('methodConverter'),
            $container->get('classlikeConverter'),
            $container->get('inheritanceResolver'),
            $container->get('interfaceImplementationResolver'),
            $container->get('traitUsageResolver'),
            $container->get('classlikeInfoBuilderProvider'),
            $container->get('typeAnalyzer'),
            $container->get('indexDatabase')
        );

        $output = $provider->getAllForFile($path);

        $this->assertEquals(2, count($output));
        $this->assertArrayHasKey('\A\FirstClass', $output);
        $this->assertArrayHasKey('\A\SecondClass', $output);
        $this->assertArrayNotHasKey('\A\Foo', $output);
        $this->assertArrayNotHasKey('\A\Bar', $output);
    }
}
