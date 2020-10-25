<?php

namespace Serenata\Analysis\Typing;

use Serenata\Indexing\Structures;

/**
 * Interface for classes that can retrieve a classlike list for a specific file.
 */
interface FileClasslikeListProviderInterface
{
    /**
     * @param Structures\File $file
     *
     * @return array<string,array<string,mixed>> mapping FQCN's to classlikes.
     */
    public function getAllForFile(Structures\File $file): array;
}
