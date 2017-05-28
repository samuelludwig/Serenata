<?php

namespace PhpIntegrator\Tests\Performance;

/**
 * @group Performance
 */
class FunctionListProvidingPerformanceTest extends AbstractPerformanceTest
{
    /**
     * @return void
     */
    public function testAllFromStubs(): void
    {
        $pathToIndex = __DIR__ . '/../../vendor/jetbrains/phpstorm-stubs';
        $dummyDatabasePath = $this->getOutputDirectory() . '/test-global-functions-stubs.sqlite';

        @unlink($dummyDatabasePath);

        $this->container->get('managerRegistry')->setDatabasePath($dummyDatabasePath);
        $this->container->get('initializeCommand')->initialize(false);

        $this->indexPath($this->container, $pathToIndex);

        $time = $this->time(function () use ($pathToIndex) {
            $this->container->get('functionListProvider')->getAll();
        });

        unlink($dummyDatabasePath);

        $this->finish($time);
    }
}
