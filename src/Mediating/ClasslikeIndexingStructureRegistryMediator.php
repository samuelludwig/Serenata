<?php

namespace PhpIntegrator\Mediating;

use Evenement\EventEmitterInterface;

use PhpIntegrator\Analysis\ClasslikeListRegistry;

use PhpIntegrator\Analysis\Conversion\ClasslikeConverter;

use PhpIntegrator\Indexing\Structures;
use PhpIntegrator\Indexing\IndexingEventName;

/**
 * Mediator that updates the classlike registry when classlike indexing events happen.
 */
class ClasslikeIndexingStructureRegistryMediator
{
    /**
     * @var ClasslikeListRegistry
     */
    private $classlikeListRegistry;

    /**
     * @var ClasslikeConverter
     */
    private $classlikeConverter;

    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @param ClasslikeListRegistry $classlikeListRegistry
     * @param ClasslikeConverter    $classlikeConverter
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct(
        ClasslikeListRegistry $classlikeListRegistry,
        ClasslikeConverter $classlikeConverter,
        EventEmitterInterface $eventEmitter
    ) {
        $this->classlikeListRegistry = $classlikeListRegistry;
        $this->classlikeConverter = $classlikeConverter;
        $this->eventEmitter = $eventEmitter;

        $this->setup();
    }

    /**
     * @return void
     */
    private function setup(): void
    {
        $this->eventEmitter->on(IndexingEventName::CLASSLIKE_UPDATED, function (Structures\Classlike $classlike) {
            $this->classlikeListRegistry->add($this->classlikeConverter->convert($classlike));
        });

        $this->eventEmitter->on(IndexingEventName::CLASSLIKE_REMOVED, function (Structures\Classlike $classlike) {
            $this->classlikeListRegistry->remove($this->classlikeConverter->convert($classlike));
        });
    }
}
