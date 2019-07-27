<?php

namespace Serenata\Tests\Performance;

/**
 * @group Performance
 */
class IndexingPerformanceTest extends AbstractPerformanceTest
{
    /**
     * @return void
     */
    public function testIndexStubs(): void
    {
        $pathToIndex = __DIR__ . '/../../vendor/jetbrains/phpstorm-stubs';
        $dummyDatabaseUri = 'file://' . $this->getOutputDirectory() . '/test-stubs.sqlite';

        @unlink($dummyDatabaseUri);

        $this->container->get('managerRegistry')->setDatabaseUri($dummyDatabaseUri);
        $this->container->get('initializeJsonRpcQueueItemHandler')->initialize(
            $this->mockJsonRpcMessageSenderInterface(),
            false
        );

        $time = $this->time(function () use ($pathToIndex) {
            $this->indexPath($this->container, $pathToIndex);
        });

        unlink($dummyDatabaseUri);

        $this->finish($time);
    }

    /**
     * @return void
     */
    public function testIndexLargeFile(): void
    {
        // This file is about 3000 lines at the time of writing.
        $pathToIndex = __DIR__ . '/../../vendor/doctrine/orm/lib/Doctrine/ORM/UnitOfWork.php';
        $dummyDatabaseUri = 'file://' . $this->getOutputDirectory() . '/test-large-file.sqlite';

        @unlink($dummyDatabaseUri);

        $this->container->get('managerRegistry')->setDatabaseUri($dummyDatabaseUri);
        $this->container->get('initializeJsonRpcQueueItemHandler')->initialize(
            $this->mockJsonRpcMessageSenderInterface(),
            false
        );

        $time = $this->time(function () use ($pathToIndex) {
            $this->indexPath($this->container, $pathToIndex);
        });

        unlink($dummyDatabaseUri);

        $this->finish($time);
    }
}
