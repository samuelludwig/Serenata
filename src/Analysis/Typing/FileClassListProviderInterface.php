<?php

namespace PhpIntegrator\Analysis\Typing;

/**
 * Interface for classes that can retrieve a class list for a specific file.
 */
interface FileClassListProviderInterface
{
    /**
     * @return array
     */
    public function getAll(): array;

    /**
     * @param string $filePath
     *
     * @return array
     */
    public function getAllForFile(string $filePath): array;
}
