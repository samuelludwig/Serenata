<?php

namespace PhpIntegrator\Tests\Integration;

use Closure;
use ReflectionClass;

use PhpIntegrator\Indexing\Indexer;

use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;

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
    private static $testContainerBuiltinStructuralElements;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->container = $this->createTestContainer();
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
        $container->get('managerRegistry')->setDatabasePath(':memory:');
        $container->get('cacheClearingEventMediator.clearableCache')->clearCache();
        $container->get('cache')->deleteAll();

        $success = $container->get('initializeCommand')->initialize(false);

        $this->assertTrue($success);
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
        $this->indexPathViaIndexer($container->get('indexer'), $testPath, false, $mayFail);
    }

    /**
     * @param Indexer $indexer
     * @param string  $testPath
     * @param bool    $useStdin
     * @param bool    $mayFail
     *
     * @return void
     */
    protected function indexPathViaIndexer(
        Indexer $indexer,
        string $testPath,
        bool $useStdin,
        bool $mayFail = false
    ): void {
        $success = $indexer->index(
            [$testPath],
            ['php', 'phpt'],
            [],
            $useStdin,
            $this->mockJsonRpcResponseSenderInterface()
        );

        if (!$mayFail) {
            $this->assertTrue($success);
        }

        $this->processOpenQueueItems();
    }

    /**
     * @return void
     */
    protected function processOpenQueueItems(): void
    {
        $refClass = new ReflectionClass(JsonRpcApplication::class);

        $refMethod = $refClass->getMethod('processQueueItem');
        $refMethod->setAccessible(true);

        while (!$this->container->get('requestQueue')->isEmpty()) {
            $refMethod->invoke(self::$application);
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
        // Test once without clearing the entities from the manager and test once after removing the entities from the
        // entity manager. This way we ensure that everything works when the entities are already loaded into memory as
        // well as when they are not (and loaded from the database instead).
        for ($i = 0; $i <= 1; ++$i) {
            $container = $this->createTestContainer();

            $stream = tmpfile();

            $sourceCodeStreamReader = new SourceCodeStreamReader(
                $this->container->get('fileSourceCodeFileReader.fileReaderFactory'),
                $this->container->get('fileSourceCodeFileReader.streamReaderFactory'),
                $stream
            );

            $indexer = new Indexer(
                $container->get('requestQueue'),
                $container->get('fileIndexer'),
                $container->get('directoryIndexRequestDemuxer'),
                $container->get('indexFilePruner'),
                $container->get('pathNormalizer'),
                $sourceCodeStreamReader,
                $container->get('directoryIndexableFileIteratorFactory')
            );

            $this->indexPathViaIndexer($indexer, $path, false);

            if ($i === 1) {
                $container->get('managerRegistry')->getManager()->clear();
            }

            $source = $sourceCodeStreamReader->getSourceCodeFromFile($path);
            $source = $afterIndex($container, $path, $source);

            if ($i === 1) {
                $container->get('managerRegistry')->getManager()->clear();
            }

            fwrite($stream, $source);
            rewind($stream);

            $this->indexPathViaIndexer($indexer, $path, true);

            if ($i === 1) {
                $container->get('managerRegistry')->getManager()->clear();
            }

            $afterReindex($container, $path, $source);

            fclose($stream);
        }
    }

    /**
     * @return JsonRpcResponseSenderInterface
     */
    protected function mockJsonRpcResponseSenderInterface(): JsonRpcResponseSenderInterface
    {
        return $this->getMockBuilder(JsonRpcResponseSenderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
