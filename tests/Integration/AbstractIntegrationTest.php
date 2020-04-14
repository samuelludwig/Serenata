<?php

namespace Serenata\Tests\Integration;

use Closure;
use ReflectionClass;

use Serenata\Indexing\IndexerInterface;
use Serenata\Indexing\PathNormalizer;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

use Serenata\UserInterface\JsonRpcApplication;
use Serenata\UserInterface\AbstractApplication;

use Serenata\Workspace\ActiveWorkspaceManager;

use Serenata\Workspace\Configuration\WorkspaceConfiguration;

use Serenata\Workspace\Workspace;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use PHPUnit\Framework\TestCase;

/**
 * Abstract base class for integration tests.
 *
 * Provides functionality using an indexing database and access to the application service container.
 */
abstract class AbstractIntegrationTest extends TestCase
{
    /**
     * @var JsonRpcApplication
     */
    private static $application;

    /**
     * @var ContainerBuilder
     */
    private static $testContainer;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @inheritDoc
     */
    public function setUp()
    {
        $this->container = $this->createTestContainer();
    }

    /**
     * @inheritDoc
     */
    public function tearDown()
    {
        // Still try to collect cyclic references every so often. See also Bootstrap.php for the reasoning.
        gc_collect_cycles();
    }

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

        $refMethod = $refClass->getMethod('getContainer');
        $refMethod->setAccessible(true);

        $container = $refMethod->invoke($application);

        return $container;
    }

    /**
     * @param AbstractApplication $application
     * @param ContainerBuilder    $container
     *
     * @return void
     */
    protected function instantiateRequiredServices(AbstractApplication $application, ContainerBuilder $container): void
    {
        $refClass = new ReflectionClass(get_class($application));

        $refMethod = $refClass->getMethod('instantiateRequiredServices');
        $refMethod->setAccessible(true);

        $container = $refMethod->invoke($application, $container);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function prepareContainer(ContainerBuilder $container): void
    {
        // Replace some container items for testing purposes.
        $container->get('managerRegistry')->setDatabaseUri(':memory:');
        $container->get('schemaInitializer')->initialize();
        $container->get('cacheClearingEventMediator.clearableCache')->clearCache();

        $container->get(ActiveWorkspaceManager::class)->setActiveWorkspace(new Workspace(new WorkspaceConfiguration(
            [],
            ':memory:',
            7.1,
            [],
            ['php', 'phpt']
        )));
    }

    /**
     * @return ContainerBuilder
     */
    protected function createTestContainer(): ContainerBuilder
    {
        if (!self::$testContainer) {
            self::$application = $this->createApplication();

            // Loading the container from the YAML file is expensive and a large slowdown to testing. As we're testing
            // integration anyway, we can share this container. We only need to ensure state is not maintained between
            // creations, which is handled by prepareContainer.
            self::$testContainer = $this->createContainer(self::$application);

            $this->instantiateRequiredServices(self::$application, self::$testContainer);
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
        $this->indexUriViaIndexer($container->get('diagnosticsSchedulingIndexer'), $testPath, null, $mayFail);
    }

    /**
     * @param IndexerInterface $indexer
     * @param string           $uri
     * @param string|null      $source
     * @param bool             $mayFail
     *
     * @return void
     */
    protected function indexUriViaIndexer(
        IndexerInterface $indexer,
        string $uri,
        ?string $source = null,
        bool $mayFail = false
    ): void {
        // TODO: Fix all callers - string $uri should already be a valid uri before being passed here.
        $normalized = $this->normalizePath($uri);

        if ($source !== null) {
            $this->container->get('textDocumentContentRegistry')->update($normalized, $source);
        } else {
            $this->container->get('textDocumentContentRegistry')->clear($normalized);
        }

        $success = $indexer->index($uri, true, $this->mockJsonRpcMessageSenderInterface());

        if (!$mayFail) {
            static::assertTrue(
                $success,
                'Indexing "' . $uri . '" should have worked, but it failed for an unknown reason instead. Does it ' .
                'perhaps contain syntax errors that cause parsing to fail?'
            );
        }

        $this->processOpenQueueItems();
    }

    /**
     * @return void
     */
    protected function processOpenQueueItems(): void
    {
        $refClass = new ReflectionClass(JsonRpcApplication::class);

        $refMethod = $refClass->getMethod('processNextQueueItem');
        $refMethod->setAccessible(true);

        if ($this->getRequestQueue()->isEmpty()) {
            return;
        }

        while (!$this->getRequestQueue()->isEmpty()) {
            $refMethod->invoke(self::$application);
        }

        // Executing timers may generate more queue items, so keep going until everything is finished.
        $this->container->get('eventLoop')->run();

        $this->processOpenQueueItems();
    }

    /**
     * @return JsonRpcQueue
     */
    protected function getRequestQueue(): JsonRpcQueue
    {
        $queue = $this->container->get(JsonRpcQueue::class);

        assert($queue instanceof JsonRpcQueue);

        return $queue;
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
     * @param ContainerBuilder $container
     * @param string           $path
     * @param string           $source
     */
    protected function indexTestFileWithSource(ContainerBuilder $container, string $path, string $source): void
    {
        $indexer = $container->get('diagnosticsSchedulingIndexer');

        $this->indexUriViaIndexer($indexer, $path, $source);
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
        // Test once without clearing the entities from the manager and test once after removing the entities from the
        // entity manager. This way we ensure that everything works when the entities are already loaded into memory as
        // well as when they are not (and loaded from the database instead).
        for ($i = 0; $i <= 1; ++$i) {
            $container = $this->createTestContainer();

            $sourceCodeStreamReader = $this->container->get('sourceCodeStreamReader');

            $indexer = $this->container->get('diagnosticsSchedulingIndexer');

            $this->indexUriViaIndexer($indexer, $path);

            if ($i === 1) {
                $container->get('managerRegistry')->getManager()->clear();
            }

            $source = $sourceCodeStreamReader->getSourceCodeFromFile($path);
            $source = $afterIndex($container, $this->normalizePath($path), $source);

            if ($i === 1) {
                $container->get('managerRegistry')->getManager()->clear();
            }

            $this->indexUriViaIndexer($indexer, $path, $source);

            if ($i === 1) {
                $container->get('managerRegistry')->getManager()->clear();
            }

            $afterReindex($container, $this->normalizePath($path), $source);
        }
    }

    /**
     * @return JsonRpcMessageSenderInterface
     */
    protected function mockJsonRpcMessageSenderInterface(): JsonRpcMessageSenderInterface
    {
        return $this->getMockBuilder(JsonRpcMessageSenderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Normalize a path for test expectations
     *
     * @param  string $path
     *
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        return (new PathNormalizer())->normalize($path);
    }
}
