<?php

namespace PhpIntegrator\Tests\Integration;

use Closure;
use ReflectionClass;

use PhpIntegrator\Indexing\Indexer;

use PhpIntegrator\UserInterface\JsonRpcApplication;
use PhpIntegrator\UserInterface\AbstractApplication;

use PhpIntegrator\Utility\SourceCodeStreamReader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Abstract base class for integration tests.
 *
 * Provides functionality using an indexing database and access to the application service container.
 */
abstract class AbstractIntegrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerBuilder
     */
    private static $testContainer;

    /**
     * @var ContainerBuilder
     */
    private static $testContainerBuiltinStructuralElements;

    /**
     * @return JsonRpcApplication
     */
    protected function createApplication(): JsonRpcApplication
    {
        return new JsonRpcApplication();
    }

    /**
     * @param AbstractApplication $application
     *
     * @return ContainerBuilder
     */
    protected function createContainer(AbstractApplication $application): ContainerBuilder
    {
        $refClass = new ReflectionClass(JsonRpcApplication::class);

        $refMethod = $refClass->getMethod('createContainer');
        $refMethod->setAccessible(true);

        $container = $refMethod->invoke($application);

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function prepareContainer(ContainerBuilder $container): void
    {
        // Replace some container items for testing purposes.
        $container->set('cache', new \Doctrine\Common\Cache\VoidCache());
        $container->get('managerRegistry')->setDatabasePath(':memory:');
        $container->get('cacheClearingEventMediator.clearableCache')->clearCache();

        $success = $container->get('initializeCommand')->initialize(false);

        $this->assertTrue($success);
    }

    /**
     * @return ContainerBuilder
     */
    protected function createTestContainer(): ContainerBuilder
    {
        if (!self::$testContainer) {
            $application = $this->createApplication();

            // Loading the container from the YAML file is expensive and a large slowdown to testing. As we're testing
            // integration anyway, we can share this container. We only need to ensure state is not maintained between
            // creations, which is handled by prepareContainer.
            self::$testContainer = $this->createContainer($application);
        }

        $this->prepareContainer(self::$testContainer, false);

        return self::$testContainer;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $testPath
     * @param bool             $mayFail
     *
     * @return void
     */
    protected function indexPath(ContainerBuilder $container, string $testPath, bool $mayFail = false): void
    {
        $success = $container->get('indexer')->reindex(
            [$testPath],
            false,
            false,
            false,
            [],
            ['php', 'phpt']
        );

        if (!$mayFail) {
            $this->assertTrue($success);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $testPath
     * @param bool             $mayFail
     *
     * @return void
     */
    protected function indexTestFile(ContainerBuilder $container, string $testPath, bool $mayFail = false): void
    {
        $this->indexPath($container, $testPath, $mayFail);
    }

    /**
     * @param string  $path
     * @param Closure $afterIndex
     * @param Closure $afterReindex
     *
     * @return void
     */
    protected function assertReindexingChanges(string $path, Closure $afterIndex, Closure $afterReindex): void
    {
        $container = $this->createTestContainer();

        $stream = tmpfile();

        $sourceCodeStreamReader = new SourceCodeStreamReader($stream);

        $indexer = new Indexer($container->get('projectIndexer'), $sourceCodeStreamReader);

        $indexer->reindex(
            [$path],
            false,
            false,
            false,
            [],
            ['phpt']
        );

        $source = $sourceCodeStreamReader->getSourceCodeFromFile($path);
        $source = $afterIndex($container, $path, $source);

        fwrite($stream, $source);
        rewind($stream);

        $indexer->reindex(
            [$path],
            true,
            false,
            false,
            [],
            ['phpt']
        );

        $afterReindex($container, $path, $source);

        fclose($stream);
    }
}
