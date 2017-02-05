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

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);

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

        $this->assertThat($output, $this->arrayHasKey('\A\FirstClass'));
        $this->assertThat($output, $this->arrayHasKey('\A\SecondClass'));
    }
}
