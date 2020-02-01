<?php

namespace Serenata\Indexing;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;

/**
 * Delegates storage to another object and emits events.
 */
final class EventEmittingStorage implements StorageInterface, EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var StorageInterface
     */
    private $delegate;

    /**
     * List of scheduled events.
     *
     * By not immediately emitting events, we ensure that, for example, multiple persists of the same entity do not emit
     * the same event multiple times to avoid costly recomputations caused by listeners.
     *
     * @var array<string,array<string|mixed[]>> Should be a array<string,tuple<string,mixed[]>> actually.
     */
    private $scheduledEvents = [];

    /**
     * @param StorageInterface $delegate
     */
    public function __construct(StorageInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function getFiles(): array
    {
        return $this->delegate->getFiles();
    }

    /**
     * @inheritDoc
     */
    public function getAccessModifiers(): array
    {
        return $this->delegate->getAccessModifiers();
    }

    /**
     * @inheritDoc
     */
    public function findStructureByFqcn(string $fqcn): ?Structures\Classlike
    {
        return $this->delegate->findStructureByFqcn($fqcn);
    }

    /**
     * @inheritDoc
     */
    public function getFileByUri(string $path): Structures\File
    {
        return $this->delegate->getFileByUri($path);
    }

    /**
     * @inheritDoc
     */
    public function persist($entity): void
    {
        $this->delegate->persist($entity);

        if ($entity instanceof Structures\FileNamespace) {
            $this->scheduleEvent(IndexingEventName::NAMESPACE_UPDATED, $entity);
        } elseif ($entity instanceof Structures\FileNamespaceImport) {
            $this->scheduleEvent(IndexingEventName::IMPORT_INSERTED, $entity);
        } elseif ($entity instanceof Structures\Constant) {
            $this->scheduleEvent(IndexingEventName::CONSTANT_UPDATED, $entity);
        } elseif ($entity instanceof Structures\Function_) {
            $this->scheduleEvent(IndexingEventName::FUNCTION_UPDATED, $entity);
        } elseif ($entity instanceof Structures\FunctionParameter) {
            $this->scheduleEvent(IndexingEventName::FUNCTION_UPDATED, $entity->getFunction());
        } elseif ($entity instanceof Structures\Classlike) {
            $this->scheduleEvent(IndexingEventName::CLASSLIKE_UPDATED, $entity);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($entity): void
    {
        $this->delegate->delete($entity);

        if ($entity instanceof Structures\FileNamespace) {
            $this->scheduleEvent(IndexingEventName::NAMESPACE_REMOVED, $entity);
        } elseif ($entity instanceof Structures\Constant) {
            $this->scheduleEvent(IndexingEventName::CONSTANT_REMOVED, $entity);
        } elseif ($entity instanceof Structures\Function_) {
            $this->scheduleEvent(IndexingEventName::FUNCTION_REMOVED, $entity);
        } elseif ($entity instanceof Structures\Classlike) {
            $this->scheduleEvent(IndexingEventName::CLASSLIKE_REMOVED, $entity);
        }
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): void
    {
        $this->delegate->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commitTransaction(): void
    {
        $this->dispatchScheduledEvents();

        $this->delegate->commitTransaction();
    }

    /**
     * @inheritDoc
     */
    public function rollbackTransaction(): void
    {
        $this->clearScheduledEvents();

        $this->delegate->rollbackTransaction();
    }

    /**
     * @param string $event
     * @param object $entity
     */
    private function scheduleEvent(string $event, $entity): void
    {
        $this->scheduledEvents[spl_object_hash($entity) . '_' . $event] = [$event, [$entity]];
    }

    /**
     * @return void
     */
    private function dispatchScheduledEvents(): void
    {
        foreach ($this->scheduledEvents as $scheduledEvent) {
            /** @var mixed[] $data */
            $data = $scheduledEvent[1];

            $this->emit($scheduledEvent[0], $data);
        }

        $this->clearScheduledEvents();
    }

    /**
     * @return void
     */
    private function clearScheduledEvents(): void
    {
        $this->scheduledEvents = [];
    }
}
