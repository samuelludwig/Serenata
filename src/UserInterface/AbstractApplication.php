<?php

namespace PhpIntegrator\UserInterface;

use PhpIntegrator\Analysis\ClearableCacheInterface;

use PhpIntegrator\Analysis\Typing\Deduction\ConfigurableDelegatingNodeTypeDeducer;

use PhpIntegrator\Indexing\IndexDatabase;
use PhpIntegrator\Indexing\CallbackStorageProxy;

use PhpIntegrator\Utility\SourceCodeStreamReader;

use Symfony\Component\Config\FileLocator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Main application class.
 */
abstract class AbstractApplication
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * The path to the database to use.
     *
     * @var string
     */
    private $databaseFile;

    /**
     * @return ContainerBuilder
     */
    protected function getContainer(): ContainerBuilder
    {
        if (!$this->container) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }

    /**
     * @return ContainerBuilder
     */
    protected function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $this->registerYamlServices($container);
        $this->registerServices($container);

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerYamlServices(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/services'));
        $loader->load('Main.yml');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function registerServices(ContainerBuilder $container): void
    {
        $container
            ->register('application', AbstractApplication::class)
            ->setSynthetic(true);

        $container->set('application', $this);

        $container
            ->register('sourceCodeStreamReader', SourceCodeStreamReader::class)
            ->setArguments([$this->getStdinStream()]);

        $container
            ->register('storageForIndexers', CallbackStorageProxy::class)
            ->setArguments([new Reference('indexDatabase'), function ($fqcn) use ($container) {
                $provider = $container->get('classlikeInfoBuilderProvider');

                if ($provider instanceof ClasslikeInfoBuilderProviderCachingProxy) {
                    $provider->clearCacheFor($fqcn);
                }
            }]);

        $container
            ->register('nodeTypeDeducer.configurableDelegator', ConfigurableDelegatingNodeTypeDeducer::class)
            ->setArguments([])
            ->setConfigurator(function (ConfigurableDelegatingNodeTypeDeducer $configurableDelegatingNodeTypeDeducer) use ($container) {
                // Avoid circular references due to two-way object usage.
                $configurableDelegatingNodeTypeDeducer->setNodeTypeDeducer($container->get('nodeTypeDeducer.instance'));
            });
    }

    /**
     * @return mixed
     */
    abstract public function run();

    /**
     * @return resource|null
     */
    abstract public function getStdinStream();

    /**
     * @param string $databaseFile
     *
     * @return static
     */
    public function setDatabaseFile(string $databaseFile)
    {
        /** @var IndexDatabase $indexDatabase */
        $indexDatabase = $this->getContainer()->get('indexDatabase');

        if (!$indexDatabase->hasDatabasePathConfigured() || $indexDatabase->getDatabasePath() !== $databaseFile) {
            $indexDatabase->setDatabasePath($databaseFile);

            /** @var ClearableCacheInterface $clearableCache */
            $clearableCache = $this->getContainer()->get('cacheClearingEventMediator.clearableCache');
            $clearableCache->clearCache();
        }

        return $this;
    }
}
