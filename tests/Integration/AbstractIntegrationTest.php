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
     * @param bool $indexBuiltinItems
     *
     * @return ContainerBuilder
     */
    protected function createTestContainer(bool $indexBuiltinItems = false): ContainerBuilder
    {
        $app = new JsonRpcApplication();

        $refClass = new ReflectionClass(JsonRpcApplication::class);

        $refMethod = $refClass->getMethod('createContainer');
        $refMethod->setAccessible(true);

        $container = $refMethod->invoke($app);

        // Replace some container items for testing purposes.
        $container->setAlias('parser', 'parser.phpParser');
        $container->set('cache', new \Doctrine\Common\Cache\VoidCache());
        $container->get('indexDatabase')->setDatabasePath(':memory:');

        $success = $container->get('initializeCommand')->initialize($indexBuiltinItems);

        $this->assertTrue($success);

        return $container;
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

    /**
     * @return ContainerBuilder
     */
    protected function createTestContainerForBuiltinStructuralElements(): ContainerBuilder
    {
        // Indexing builtin items is a fairy large performance hit to run every test, so keep the property static.
        if (!self::$testContainerBuiltinStructuralElements) {
            self::$testContainerBuiltinStructuralElements = $this->createTestContainer(true);
        }

        return self::$testContainerBuiltinStructuralElements;
    }
}
