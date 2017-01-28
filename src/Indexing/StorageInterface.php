<?php

namespace PhpIntegrator\Indexing;

/**
 * Defines functionality that must be exposed by classes that can interact with a storage medium for persisting data
 * related to the index.
 */
interface StorageInterface
{
    /**
     * Retrieves a list of files mapped to their last indexed date (as DateTime).
     *
     * @return array
     */
    public function getFileModifiedMap(): array;

    /**
    * Retrieves a list of access modifiers mapped to their ID.
    *
    * @return array
    */
    public function getAccessModifierMap(): array;

     /**
     * Retrieves a list of structural element types mapped to their ID.
     *
     * @return array
     */
    public function getStructureTypeMap(): array;

    /**
     * Retrieves the ID of the file with the specified path.
     *
     * @param string $path
     *
     * @return int|null
     */
    public function getFileId(string $path): ?int;

    /**
     * @param string $path
     *
     * @return void
     */
    public function deleteFile(string $path): void;

    /**
     * @param array  $data
     *
     * @return int The unique identifier assigned to the inserted data.
     */
    public function insertStructure(array $data): int;

    /**
     * Inserts the specified index item into the storage.
     *
     * @param string $indexStorageItem
     * @param array  $data
     *
     * @return int The unique identifier assigned to the inserted data.
     */
    public function insert(string $indexStorageItem, array $data): int;

    /**
     * Updates the specified index item.
     *
     * @param string    $indexStorageItem
     * @param int|array $id
     * @param array     $data
     *
     * @return void
     */
    public function update(string $indexStorageItem, $id, array $data): void;

    /**
     * Starts a transaction.
     */
    public function beginTransaction(): void;

    /**
     * Commits a transaction.
     */
    public function commitTransaction(): void;

    /**
     * Rolls back a transaction.
     */
    public function rollbackTransaction(): void;
}
