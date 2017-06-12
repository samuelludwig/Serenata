<?php

namespace PhpIntegrator\Indexing;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;

/**
 * Delegates storage to another object and emits events.
 */
class EventEmittingStorage implements StorageInterface, EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var string
     */
    public const EVENT_NAMESPACE_UPDATED = 'namespaceUpdated';

    /**
     * @var string
     */
    public const EVENT_NAMESPACE_REMOVED = 'namespaceRemoved';

    /**
     * @var string
     */
    public const EVENT_IMPORT_INSERTED = 'importInserted';

    /**
     * @var string
     */
    public const EVENT_CONSTANT_UPDATED = 'constantUpdated';

    /**
     * @var string
     */
    public const EVENT_CONSTANT_REMOVED = 'constantRemoved';

    /**
     * @var string
     */
    public const EVENT_FUNCTION_UPDATED = 'functionUpdated';

    /**
     * @var string
     */
    public const EVENT_FUNCTION_REMOVED = 'functionRemoved';

    /**
     * @var string
     */
    public const EVENT_STRUCTURE_UPDATED = 'structureUpdated';

    /**
     * @var string
     */
    public const EVENT_STRUCTURE_REMOVED = 'structureRemoved';

    /**
     * @var StorageInterface
     */
    private $delegate;

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
    public function findStructureByFqcn(string $fqcn): ?Structures\Structure
    {
        return $this->delegate->findStructureByFqcn($fqcn);
    }

    /**
     * @inheritDoc
     */
    public function getFileByPath(string $path): Structures\File
    {
        return $this->delegate->getFileByPath($path);
    }

    /**
     * @inheritDoc
     */
    public function persist($entity): void
    {
        $this->delegate->persist($entity);

        if ($entity instanceof Structures\FileNamespace) {
            $this->emit(self::EVENT_NAMESPACE_UPDATED, [$entity]);
        } elseif ($entity instanceof Structures\FileNamespaceImport) {
            $this->emit(self::EVENT_IMPORT_INSERTED);
        } elseif ($entity instanceof Structures\Constant) {
            $this->emit(self::EVENT_CONSTANT_UPDATED, [$entity]);
        } elseif ($entity instanceof Structures\Function_) {
            $this->emit(self::EVENT_FUNCTION_UPDATED, [$entity]);
        } elseif ($entity instanceof Structures\FunctionParameter) {
            $this->emit(self::EVENT_FUNCTION_UPDATED, [$entity->getFunction()]);
        } elseif ($entity instanceof Structures\Structure) {
            $this->emit(self::EVENT_STRUCTURE_UPDATED, [$entity]);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($entity): void
    {
        $this->delegate->delete($entity);

        if ($entity instanceof Structures\FileNamespace) {
            $this->emit(self::EVENT_NAMESPACE_REMOVED, [$entity]);
        } elseif ($entity instanceof Structures\Constant) {
            $this->emit(self::EVENT_CONSTANT_REMOVED, [$entity]);
        } elseif ($entity instanceof Structures\Function_) {
            $this->emit(self::EVENT_FUNCTION_REMOVED, [$entity]);
        } elseif ($entity instanceof Structures\Structure) {
            $this->emit(self::EVENT_STRUCTURE_REMOVED, [$entity]);
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
        $this->delegate->commitTransaction();
    }

    /**
     * @inheritDoc
     */
    public function rollbackTransaction(): void
    {
        $this->delegate->rollbackTransaction();
    }
}
