<?php

namespace Serenata\Indexing;

use IteratorIterator;

/**
 * Iterator that iterates all indexable files for a path.
 */
final class IndexableFileIterator extends IteratorIterator
{
    /**
     * @param string   $uri
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     */
    public function __construct(string $uri, array $extensionsToIndex, array $globsToExclude = [])
    {
        $iterator = new Iterating\MultiRecursivePathIterator([$uri]);
        $iterator = new Iterating\ExtensionFilterIterator($iterator, $extensionsToIndex);
        $iterator = new Iterating\ExclusionFilterIterator($iterator, $globsToExclude);

        parent::__construct($iterator);
    }
}
