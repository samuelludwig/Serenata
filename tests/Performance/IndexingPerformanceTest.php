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
}
