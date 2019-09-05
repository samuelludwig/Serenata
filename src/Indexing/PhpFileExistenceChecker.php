<?php

namespace Serenata\Indexing;

/**
 * Checks if a file exists via PHP's {@see file_exists} method.
 */
final class PhpFileExistenceChecker implements FileExistenceCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function exists(string $fileName): bool
    {
        return file_exists($fileName);
    }
}
