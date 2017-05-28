<?php

namespace PhpIntegrator\Tests\Performance;

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
        $dummyDatabasePath = $this->getOutputDirectory() . '/test-stubs.sqlite';

        @unlink($dummyDatabasePath);

        $this->container->get('managerRegistry')->setDatabasePath($dummyDatabasePath);
        $this->container->get('initializeCommand')->initialize(false);

        $this->indexPath($this->container, $pathToIndex);

        $this->assertTrue(true);

        unlink($dummyDatabasePath);
    }

    /**
     * @return void
     */
    public function testIndexLargeFile(): void
    {
        // This file is about 3000 lines at the time of writing.
        $pathToIndex = __DIR__ . '/../../vendor/doctrine/orm/lib/Doctrine/ORM/UnitOfWork.php';
        $dummyDatabasePath = $this->getOutputDirectory() . '/test-large-file.sqlite';

        @unlink($dummyDatabasePath);

        $this->container->get('managerRegistry')->setDatabasePath($dummyDatabasePath);
        $this->container->get('initializeCommand')->initialize(false);

        $this->indexPath($this->container, $pathToIndex);

        $this->assertTrue(true);

        unlink($dummyDatabasePath);
    }
}
