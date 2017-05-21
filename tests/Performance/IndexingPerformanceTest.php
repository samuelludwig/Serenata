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
    public function testInitializationWithStubs(): void
    {
        $pathToIndex = __DIR__ . '/../../vendor/jetbrains/phpstorm-stubs';
        $dummyDatabasePath = $this->getOutputDirectory() . '/test-stubs.sqlite';

        @unlink($dummyDatabasePath);

        $container = $this->createTestContainer();
        $container->get('managerRegistry')->setDatabasePath($dummyDatabasePath);
        $container->get('initializeCommand')->initialize(false);

        $this->indexPath($container, $pathToIndex);

        $this->assertTrue(true);

        unlink($dummyDatabasePath);
    }
}
