<?php

namespace PhpIntegrator\Indexing;

/**
 * Defines functionality that must be exposed by classes that can interact with a storage medium for persisting data
 * related to the index.
 */
interface StorageInterface
{
    /**
     * @return Structures\File[]
     */
    public function getFiles(): array;

    /**
    * @return Structures\AccessModifier[]
    */
    public function getAccessModifiers(): array;

     /**
     * @return Structures\StructureType[]
     */
    public function getStructureTypes(): array;

    /**
     * @param string $fqcn
     *
     * @return Structures\Structure|null
     */
    public function findStructureByFqcn(string $fqcn): ?Structures\Structure;

    /**
     * @param string $path
     *
     * @return Structures\File|null
     */
    public function findFileByPath(string $path): ?Structures\File;

    /**
     * @param object $entity
     *
     * @return void
     */
    public function persist($entity): void;

    /**
     * @param object $entity
     *
     * @return void
     */
    public function delete($entity): void;

    /**
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * @return void
     */
    public function commitTransaction(): void;

    /**
     * @return void
     */
    public function rollbackTransaction(): void;
}
