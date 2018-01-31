<?php

namespace PhpIntegrator\Indexing;

/**
 * Interface for classes that can check if a file exists.
 */
interface FileExistenceCheckerInterface
{
    /**
     * @param string $fileName
     *
     * @return bool
     */
    public function exists(string $fileName): bool;
}
