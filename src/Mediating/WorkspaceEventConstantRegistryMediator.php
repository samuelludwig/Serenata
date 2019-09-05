<?php

namespace Serenata\Mediating;

use Evenement\EventEmitterInterface;

use Serenata\Analysis\ConstantListRegistry;

use Serenata\Indexing\WorkspaceEventName;

/**
 * Mediator that updates the constant registry when workspace events happen.
 */
final class WorkspaceEventConstantRegistryMediator
{
    /**
     * @var ConstantListRegistry
     */
    private $constantListRegistry;

    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @param ConstantListRegistry  $constantListRegistry
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct(
        ConstantListRegistry $constantListRegistry,
        EventEmitterInterface $eventEmitter
    ) {
        $this->constantListRegistry = $constantListRegistry;
        $this->eventEmitter = $eventEmitter;

        $this->setup();
    }

    /**
     * @return void
     */
    private function setup(): void
    {
        $this->eventEmitter->on(WorkspaceEventName::CHANGED, function (string $filePath) {
            $this->constantListRegistry->reset();
        });
    }
}
