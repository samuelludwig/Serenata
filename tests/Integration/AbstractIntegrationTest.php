<?php

namespace PhpIntegrator\Tests\Integration;

use ReflectionClass;

use PhpIntegrator\UserInterface\JsonRpcApplication;

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
     * @return ContainerBuilder
     */
    protected function createApplicationContainer(): ContainerBuilder
    {
        $app = new JsonRpcApplication();

        $refClass = new ReflectionClass(JsonRpcApplication::class);

        $refMethod = $refClass->getMethod('createContainer');
        $refMethod->setAccessible(true);

        $container = $refMethod->invoke($app);

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     * @param bool             $indexBuiltinItems
     *
     * @return void
     */
    protected function prepareContainer(ContainerBuilder $container, bool $indexBuiltinItems): void
    {
        // Replace some container items for testing purposes.
        $container->setAlias('parser', 'parser.phpParser');
        $container->set('cache', new \Doctrine\Common\Cache\VoidCache());
        $container->get('indexDatabase')->setDatabasePath(':memory:');
        $container->get('cacheClearingEventMediator.clearableCache')->clearCache();

        $success = $container->get('initializeCommand')->initialize($indexBuiltinItems);

        $this->assertTrue($success);
    }

    /**
     * @return ContainerBuilder
     */
    protected function createTestContainer(): ContainerBuilder
    {
        if (!self::$testContainer) {
            // Loading the container from the YAML file is expensive and a large slowdown to testing. As we're testing
            // integration anyway, we can share this container. We only need to ensure state is not maintained between
            // creations, which is handled by prepareContainer.
            self::$testContainer = $this->createApplicationContainer();
        }

        $this->prepareContainer(self::$testContainer, false);

        return self::$testContainer;
    }

    /**
     * @return ContainerBuilder
     */
    protected function createTestContainerForBuiltinStructuralElements(): ContainerBuilder
    {
        if (!self::$testContainerBuiltinStructuralElements) {
            self::$testContainerBuiltinStructuralElements = $this->createApplicationContainer();

            // Indexing builtin items is a fairy large performance hit to run every test, so keep the property static.
            $this->prepareContainer(self::$testContainerBuiltinStructuralElements, true);
        }

        return self::$testContainerBuiltinStructuralElements;
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
        $success = $container->get('indexer')->reindex(
            [$testPath],
            false,
            false,
            false,
            [],
            ['phpt']
        );

        if (!$mayFail) {
            $this->assertTrue($success);
        }
    }
}
