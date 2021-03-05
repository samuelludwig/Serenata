<?php

namespace Serenata\Indexing;

use SplFileInfo;
use IteratorIterator;

/**
 * Iterator that iterates all indexable files in a directory.
 *
 * @extends IteratorIterator<int,SplFileInfo,DirectoryIndexableFileIterator>
 */
final class DirectoryIndexableFileIterator extends IteratorIterator
{
    /**
     * @param Structures\File[] $filesInIndex
     * @param string            $uri
     * @param string[]          $extensionsToIndex
     * @param string[]          $globsToExclude
     */
    public function __construct(array $filesInIndex, string $uri, array $extensionsToIndex, array $globsToExclude = [])
    {
        $iterator = new IndexableFileIterator($uri, $extensionsToIndex, $globsToExclude);
        $iterator = new Iterating\ModificationTimeFilterIterator(new IteratorIterator($iterator), $filesInIndex);

        parent::__construct($iterator);
    }
}
