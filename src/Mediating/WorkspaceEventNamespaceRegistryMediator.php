<?php

namespace Serenata\Mediating;

use Evenement\EventEmitterInterface;

use Serenata\Analysis\NamespaceListRegistry;

use Serenata\Indexing\WorkspaceEventName;

/**
 * Mediator that updates the namespace registry when workspace events happen.
 */
final class WorkspaceEventNamespaceRegistryMediator
{
    /**
     * @var NamespaceListRegistry
     */
    private $namespaceListRegistry;

    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @param NamespaceListRegistry  $namespaceListRegistry
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct(
        NamespaceListRegistry $namespaceListRegistry,
        EventEmitterInterface $eventEmitter
    ) {
        $this->namespaceListRegistry = $namespaceListRegistry;
        $this->eventEmitter = $eventEmitter;

        $this->setup();
    }

    /**
     * @return void
     */
    private function setup(): void
    {
        $this->eventEmitter->on(WorkspaceEventName::CHANGED, function (string $filePath): void {
            $this->namespaceListRegistry->reset();
        });
    }
}
