<?php

namespace Serenata\UserInterface;

use Throwable;
use LogicException;

use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;
use Serenata\Analysis\Typing\Deduction\ConfigurableDelegatingNodeTypeDeducer;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * To do
 */
class NodeTypeDeducerConfigurableDelegatorConfigurator
{
    /**
     * @var ContainerBuilder
     */
    private static $container;

    /**
     * Constructor.
     *
     * @param ContainerBuilder $container
     */
    public function __construct(ContainerBuilder $container)
    {
        self::$container = $container;
    }

    /**
     * @param ConfigurableDelegatingNodeTypeDeducer $deducer
     */
    public static function configure(ConfigurableDelegatingNodeTypeDeducer $deducer): void
    {
        try {
            /** @var NodeTypeDeducerInterface $nodeTypeDeducer */
            $nodeTypeDeducer = static::$container->get('nodeTypeDeducer.instance');
        } catch (Throwable $e) {
            throw new LogicException(
                'Could not fetch expected service nodeTypeDeducer.instance from container, its definition may ' .
                'contain errors',
                0,
                $e
            );
        }

        // Avoid circular references due to two-way object usage.
        $deducer->setNodeTypeDeducer($nodeTypeDeducer);
    }
}
