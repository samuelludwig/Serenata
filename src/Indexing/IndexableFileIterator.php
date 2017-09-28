<?php

namespace PhpIntegrator\Indexing;

use IteratorIterator;

/**
 * Iterator that iterates all indexable files for a path.
 */
final class IndexableFileIterator extends IteratorIterator
{
    /**
     * @param string[] $paths
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     */
    public function __construct(array $paths, array $extensionsToIndex, array $globsToExclude = [])
    {
        $iterator = new Iterating\MultiRecursivePathIterator($paths);
        $iterator = new Iterating\ExtensionFilterIterator($iterator, $extensionsToIndex);
        $iterator = new Iterating\ExclusionFilterIterator($iterator, $globsToExclude);

        parent::__construct($iterator);
    }
}
