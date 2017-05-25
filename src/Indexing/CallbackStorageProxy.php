<?php

namespace PhpIntegrator\Indexing;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;

/**
 * Proxy for classes implementing {@see StorageInterface} that will invoke callback functions when specific methods are
 * called.
 */
class CallbackStorageProxy implements StorageInterface, EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var string
     */
    public const EVENT_NAMESPACE_INSERTED = 'namespaceInserted';

    /**
     * @var string
     */
    public const EVENT_IMPORT_INSERTED = 'importInserted';

    /**
     * @var StorageInterface
     */
    private $delegate;

    /**
     * @var callable
     */
    private $insertStructureCallback;

    /**
     * @param StorageInterface $delegate
     * @param callable         $insertStructureCallback
     */
    public function __construct(StorageInterface $delegate, callable $insertStructureCallback)
    {
        $this->delegate = $delegate;
        $this->insertStructureCallback = $insertStructureCallback;
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
    public function getStructureTypes(): array
    {
        return $this->delegate->getStructureTypes();
    }

    /**
     * @inheritDoc
     */
    public function findStructureByFqcn(string $fqcn): ?Structures\Structure
    {
        return $this->delegate->findStructureByFqcn($path);
    }

    /**
     * @inheritDoc
     */
    public function findFileByPath(string $path): ?Structures\File
    {
        return $this->delegate->findFileByPath($path);
    }

    /**
     * @inheritDoc
     */
    public function persist($entity): void
    {
        if ($entity instanceof Structures\Structure) {
            $callback = $this->insertStructureCallback;
            $callback($data['fqcn']);
        }

        $this->delegate->persist($entity);

        if ($entity instanceof Structures\FileNamespace) {
            $this->emit(self::EVENT_NAMESPACE_INSERTED);
        } elseif ($entity instanceof Structures\FileNamespaceImport) {
            $this->emit(self::EVENT_IMPORT_INSERTED);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($entity): void
    {
        $this->delegate->delete($entity);
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
