<?php

namespace Serenata\Tests\Performance;

/**
 * @group Performance
 */
final class FunctionListProvidingPerformanceTest extends AbstractPerformanceTest
{
    /**
     * @return void
     */
    public function testFetchAllColdFromStubs(): void
    {
        $pathToIndex = __DIR__ . '/../../vendor/jetbrains/phpstorm-stubs';
        $dummyDatabaseUri = 'file://' . $this->getOutputDirectory() . '/test-global-functions-stubs.sqlite';

        @unlink($dummyDatabaseUri);

        $this->container->get('managerRegistry')->setDatabaseUri($dummyDatabaseUri);
        $this->container->get('initializeJsonRpcQueueItemHandler')->initialize(
            $this->mockJsonRpcMessageSenderInterface(),
            false
        );

        $this->indexPath($this->container, $pathToIndex);

        $time = $this->time(function () use ($pathToIndex) {
            $this->container->get('functionListProvider')->getAll();
        });

        unlink($dummyDatabaseUri);

        $this->finish($time);
    }

    /**
     * @return void
     */
    public function testFetchAllHotFromStubs(): void
    {
        $pathToIndex = __DIR__ . '/../../vendor/jetbrains/phpstorm-stubs';
        $dummyDatabaseUri = $this->getOutputDirectory() . '/test-global-functions-stubs.sqlite';

        @unlink($dummyDatabaseUri);

        $this->container->get('managerRegistry')->setDatabaseUri($dummyDatabaseUri);
        $this->container->get('initializeJsonRpcQueueItemHandler')->initialize(
            $this->mockJsonRpcMessageSenderInterface(),
            false
        );

        $this->indexPath($this->container, $pathToIndex);
        $this->container->get('functionListProvider')->getAll();

        $time = $this->time(function () use ($pathToIndex) {
            $this->container->get('functionListProvider')->getAll();
        });

        unlink($dummyDatabaseUri);

        $this->finish($time);
    }
}
