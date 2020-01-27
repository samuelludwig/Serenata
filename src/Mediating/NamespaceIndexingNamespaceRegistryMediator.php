<?php

namespace Serenata\Mediating;

use Evenement\EventEmitterInterface;

use Serenata\Analysis\NamespaceListRegistry;

use Serenata\Analysis\Conversion\NamespaceConverter;

use Serenata\Indexing\Structures;
use Serenata\Indexing\IndexingEventName;

/**
 * Mediator that updates the namespace registry when namespace indexing events happen.
 */
final class NamespaceIndexingNamespaceRegistryMediator
{
    /**
     * @var NamespaceListRegistry
     */
    private $namespaceListRegistry;

    /**
     * @var NamespaceConverter
     */
    private $namespaceConverter;

    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @param NamespaceListRegistry $namespaceListRegistry
     * @param NamespaceConverter    $namespaceConverter
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct(
        NamespaceListRegistry $namespaceListRegistry,
        NamespaceConverter $namespaceConverter,
        EventEmitterInterface $eventEmitter
    ) {
        $this->namespaceListRegistry = $namespaceListRegistry;
        $this->namespaceConverter = $namespaceConverter;
        $this->eventEmitter = $eventEmitter;

        $this->setup();
    }

    /**
     * @return void
     */
    private function setup(): void
    {
        $this->eventEmitter->on(
            IndexingEventName::NAMESPACE_UPDATED,
            function (Structures\FileNamespace $namespace): void {
                $this->namespaceListRegistry->add($this->namespaceConverter->convert($namespace));
            }
        );

        $this->eventEmitter->on(
            IndexingEventName::NAMESPACE_REMOVED,
            function (Structures\FileNamespace $namespace): void {
                $this->namespaceListRegistry->remove($this->namespaceConverter->convert($namespace));
            }
        );
    }
}
