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
    static $testContainerBuiltinStructuralElements;

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

        $success = $container->get('initializeCommand')->initialize($indexBuiltinItems);

        $this->assertTrue($success);
    }

    /**
     * @return ContainerBuilder
     */
    protected function createTestContainer(): ContainerBuilder
    {
        $container = $this->createApplicationContainer();

        $this->prepareContainer($container, false);

        return $container;
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
