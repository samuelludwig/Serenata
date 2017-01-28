<?php

namespace PhpIntegrator\Indexing;

/**
 * Proxy for classes implementing {@see StorageInterface} that will invoke callback functions when specific methods are
 * called.
 */
class CallbackStorageProxy implements StorageInterface
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var callable
     */
    protected $insertStructureCallback;

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
