<?php

namespace PhpIntegrator\Indexing;

use IteratorIterator;

/**
 * Iterator that iterates all indexable files in a directory.
 */
final class DirectoryIndexableFileIterator extends IteratorIterator
{
    /**
     * @param Structures\File $filesInIndex
     * @param string[]        $paths
     * @param string[]        $extensionsToIndex
     * @param string[]        $globsToExclude
     */
    public function __construct(
        array $filesInIndex,
        array $paths,
        array $extensionsToIndex,
        array $globsToExclude = []
    ) {
        $iterator = new IndexableFileIterator($paths, $extensionsToIndex, $globsToExclude);
        $iterator = new Iterating\ModificationTimeFilterIterator($iterator, $filesInIndex);

        parent::__construct($iterator);
    }
}
