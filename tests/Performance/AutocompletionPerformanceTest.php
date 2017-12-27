<?php

namespace PhpIntegrator\Tests\Performance;

/**
 * @group Performance
 */
class AutocompletionPerformanceTest extends AbstractPerformanceTest
{
    /**
     * @return void
     */
    public function testProvideAllFromStubs(): void
    {
        $pathToIndex = __DIR__ . '/../../vendor/jetbrains/phpstorm-stubs';
        $dummyDatabasePath = $this->getOutputDirectory() . '/test-stubs.sqlite';

        @unlink($dummyDatabasePath);

        $this->container->get('managerRegistry')->setDatabasePath($dummyDatabasePath);
        $this->container->get('initializeCommand')->initialize(
            $this->mockJsonRpcResponseSenderInterface(),
            false
        );

        $this->indexPath($this->container, $pathToIndex);

        $testFilePath = $pathToIndex . '/Core/Core.php';

        $time = $this->time(function () use ($testFilePath) {
            $suggestions = iterator_to_array($this->container->get('autocompletionProvider')->provide(
                $this->container->get('storage')->getFileByPath($testFilePath),
                $this->container->get('sourceCodeStreamReader')->getSourceCodeFromFile($testFilePath),
                6
            ));
        });

        unlink($dummyDatabasePath);

        $this->finish($time);
    }
}
