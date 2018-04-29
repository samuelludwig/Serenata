<?php

namespace PhpIntegrator\UserInterface;

use PhpIntegrator\Analysis\Typing\Deduction\ConfigurableDelegatingNodeTypeDeducer;

use Symfony\Component\Config\FileLocator;

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
    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $this->registerYamlServices($container);
        $this->registerServices($container);

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerYamlServices(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/services'));
        $loader->load('Main.yml');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    private function registerServices(ContainerBuilder $container): void
    {
        $container
            ->register('application', AbstractApplication::class)
            ->setSynthetic(true);

        $container->set('application', $this);

        $container
            ->register('nodeTypeDeducer.configurableDelegator', ConfigurableDelegatingNodeTypeDeducer::class)
            ->setArguments([])
            ->setConfigurator(function (ConfigurableDelegatingNodeTypeDeducer $configurableDelegatingNodeTypeDeducer) use ($container) {
                // Avoid circular references due to two-way object usage.
                $configurableDelegatingNodeTypeDeducer->setNodeTypeDeducer($container->get('nodeTypeDeducer.instance'));
            });
    }

    /**
     * Instantiates services that are required for the application to function correctly.
     *
     * Usually we prefer to rely on lazy loading of services, but some services aren't explicitly required by any other
     * service, but do provide necessary interaction (i.e. they are required by the application itself).
     *
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function instantiateRequiredServices(ContainerBuilder $container): void
    {
        // TODO: Need to refactor this at some point to have more select cache clearing and to not instantiate multiple
        // mediators.
        $container->get('cacheClearingEventMediator1');
        $container->get('cacheClearingEventMediator2');
        $container->get('cacheClearingEventMediator3');
        $container->get('functionIndexingFunctionRegistryMediator');
        $container->get('constantIndexingConstantRegistryMediator');
        $container->get('classlikeIndexingStructureRegistryMediator');
        $container->get('namespaceIndexingNamespaceRegistryMediator');

        $container->get('workspaceEventConstantRegistryMediator');
        $container->get('workspaceEventFunctionRegistryMediator');
        $container->get('workspaceEventClasslikeRegistryMediator');
        $container->get('workspaceEventNamespaceRegistryMediator');
    }

    /**
     * @return mixed
     */
    abstract public function run();
}
