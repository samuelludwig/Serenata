<?php

namespace Serenata\Mediating;

use Evenement\EventEmitterInterface;

use Serenata\Analysis\FunctionListRegistry;

use Serenata\Indexing\WorkspaceEventName;

/**
 * Mediator that updates the function registry when workspace events happen.
 */
class WorkspaceEventFunctionRegistryMediator
{
    /**
     * @var FunctionListRegistry
     */
    private $functionListRegistry;

    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @param FunctionListRegistry  $functionListRegistry
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct(
        FunctionListRegistry $functionListRegistry,
        EventEmitterInterface $eventEmitter
    ) {
        $this->functionListRegistry = $functionListRegistry;
        $this->eventEmitter = $eventEmitter;

        $this->setup();
    }

    /**
     * @return void
     */
    private function setup(): void
    {
        $this->eventEmitter->on(WorkspaceEventName::CHANGED, function (string $filePath) {
            $this->functionListRegistry->reset();
        });
    }
}
