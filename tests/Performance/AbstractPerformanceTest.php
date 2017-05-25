<?php

namespace PhpIntegrator\Tests\Performance;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

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
        return __DIR__ . '/Output';
    }
}
