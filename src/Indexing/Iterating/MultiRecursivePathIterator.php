<?php

namespace Serenata\Indexing\Iterating;

use SplFileInfo;
use ArrayIterator;
use AppendIterator;
use FilesystemIterator;
use UnexpectedValueException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Recursively iterates over multiple paths.
 */
final class MultiRecursivePathIterator extends AppendIterator
{
    /**
     * @param string[] $uris
     *
     * @throws UnexpectedValueException
     */
    public function __construct(array $uris)
    {
        parent::__construct();

        $fileInfoIterators = [];

        foreach ($uris as $uri) {
            $fileInfo = new SplFileInfo($uri);

            if ($fileInfo->isDir()) {
                $directoryIterator = new RecursiveDirectoryIterator(
                    $fileInfo->getPathname(),
                    FilesystemIterator::KEY_AS_PATHNAME |
                    FilesystemIterator::SKIP_DOTS |
                    FilesystemIterator::FOLLOW_SYMLINKS
                );

                $iterator = new RecursiveIteratorIterator(
                    $directoryIterator,
                    RecursiveIteratorIterator::LEAVES_ONLY,
                    RecursiveIteratorIterator::CATCH_GET_CHILD
                );

                $fileInfoIterators[] = $iterator;
            } elseif ($fileInfo->isFile()) {
                $fileInfoIterators[] = new ArrayIterator([$fileInfo]);
            } else {
                throw new UnexpectedValueException(
                    'The specified file or directory "' . $fileInfo->getPathname() . '" does not exist!'
                );
            }
        }

        foreach ($fileInfoIterators as $fileInfoIterator) {
            $this->append($fileInfoIterator);
        }
    }
}
