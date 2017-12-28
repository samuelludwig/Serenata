<?php

namespace PhpIntegrator\Mediating;

use Evenement\EventEmitterInterface;

use PhpIntegrator\Analysis\ClasslikeListRegistry;

use PhpIntegrator\Indexing\WorkspaceEventName;

/**
 * Mediator that updates the structure registry when workspace events happen.
 */
class WorkspaceEventStructureRegistryMediator
{
    /**
     * @var ClasslikeListRegistry
     */
    private $classlikeListRegistry;

    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @param ClasslikeListRegistry  $classlikeListRegistry
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct(
        ClasslikeListRegistry $classlikeListRegistry,
        EventEmitterInterface $eventEmitter
    ) {
        $this->classlikeListRegistry = $classlikeListRegistry;
        $this->eventEmitter = $eventEmitter;

        $this->setup();
    }

    /**
     * @return void
     */
    protected function setup(): void
    {
        $this->eventEmitter->on(WorkspaceEventName::CHANGED, function (string $filePath) {
            $this->classlikeListRegistry->reset();
        });
    }
}
