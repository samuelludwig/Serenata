<?php

namespace PhpIntegrator\Analysis\Typing;

use RuntimeException;

use PhpIntegrator\Indexing\Structures;

/**
 * Interface for classes that can retrieve a classlike list for a specific file.
 */
interface FileClasslikeListProviderInterface
{
    /**
     * @param Structures\File $file
     *
     * @throws RuntimeException
     *
     * @return array array<string, array> mapping FQCN's to classlikes.
     */
    public function getAllForFile(Structures\File $file): array;
}
