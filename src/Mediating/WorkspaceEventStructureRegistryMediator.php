<?php

namespace Serenata\Mediating;

use Evenement\EventEmitterInterface;

use Serenata\Analysis\ClasslikeListRegistry;

use Serenata\Indexing\WorkspaceEventName;

/**
 * Mediator that updates the structure registry when workspace events happen.
 */
final class WorkspaceEventStructureRegistryMediator
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
    private function setup(): void
    {
        $this->eventEmitter->on(WorkspaceEventName::CHANGED, function (string $filePath): void {
            $this->classlikeListRegistry->reset();
        });
    }
}
