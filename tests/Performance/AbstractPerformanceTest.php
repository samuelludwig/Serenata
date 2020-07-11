<?php

namespace Serenata\Tests\Performance;

use Closure;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * @group Performance
 */
abstract class AbstractPerformanceTest extends AbstractIntegrationTest
{
    /**
     * @return string
     */
    protected function getOutputDirectory(): string
    {
        return 'file://' . $this->normalizePath(__DIR__ . '/Output');
    }

    /**
     * @param Closure $closure
     *
     * @return float
     */
    protected function time(Closure $closure): float
    {
        $time = microtime(true);

        $closure();

        return (microtime(true) - $time) * 1000;
    }

    /**
     * @param float $time
     *
     * @return void
     */
    protected function finish(float $time): void
    {
        self::markTestSkipped("Took {$time} milliseconds (" . ($time / 1000) . " seconds)");
    }


    /**
     * @return ActiveWorkspaceManager
     */
    protected function getActiveWorkspaceManager(): ActiveWorkspaceManager
    {
        $manager = $this->container->get(ActiveWorkspaceManager::class);

        assert($manager instanceof ActiveWorkspaceManager);

        return $manager;
    }
}
