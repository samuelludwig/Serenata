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
    private $storage;

    /**
     * @var callable
     */
    private $insertStructureCallback;

    /**
     * @param StorageInterface $storage
     * @param callable         $insertStructureCallback
     */
    public function __construct(StorageInterface $storage, callable $insertStructureCallback)
    {
        $this->storage = $storage;
        $this->insertStructureCallback = $insertStructureCallback;
    }

    /**
     * @inheritDoc
     */
    public function getFileModifiedMap(): array
    {
        return $this->storage->getFileModifiedMap();
    }

    /**
     * @inheritDoc
     */
    public function getAccessModifierMap(): array
    {
        return $this->storage->getAccessModifierMap();
    }

    /**
     * @inheritDoc
     */
    public function getStructureTypeMap(): array
    {
        return $this->storage->getStructureTypeMap();
    }

    /**
     * @inheritDoc
     */
    public function getFileId(string $path): ?int
    {
        return $this->storage->getFileId($path);
    }

    /**
     * @inheritDoc
     */
    public function deleteFile(string $path): void
    {
        $this->storage->deleteFile($path);
    }

    /**
     * @inheritDoc
     */
    public function insertStructure(array $data): int
    {
        $callback = $this->insertStructureCallback;
        $callback($data['fqcn']);

        return $this->storage->insertStructure($data);
    }

    /**
     * @inheritDoc
     */
    public function insertNamespace(array $data): int
    {
        $id = $this->storage->insertNamespace($data);

        $this->emit(self::EVENT_NAMESPACE_INSERTED);

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function insertImport(array $data): int
    {
        $id = $this->storage->insertImport($data);

        $this->emit(self::EVENT_IMPORT_INSERTED);

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function insert(string $indexStorageItem, array $data): int
    {
        return $this->storage->insert($indexStorageItem, $data);
    }

    /**
     * @inheritDoc
     */
    public function update(string $indexStorageItem, $id, array $data): void
    {
        $this->storage->update($indexStorageItem, $id, $data);
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): void
    {
        $this->storage->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commitTransaction(): void
    {
        $this->storage->commitTransaction();
    }

    /**
     * @inheritDoc
     */
    public function rollbackTransaction(): void
    {
        $this->storage->rollbackTransaction();
    }
}
