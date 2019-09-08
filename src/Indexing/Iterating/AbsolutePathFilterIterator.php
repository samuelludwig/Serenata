<?php

namespace Serenata\Indexing\Iterating;

use Symfony\Component\Finder\Iterator\PathFilterIterator;

/**
 * Filters files by path patterns (e.g. file:///some/special/dir).
 *
 * Variant of Symfony Finder's PathFilterIterator that works with absolute paths instead of relative paths.
 */
final class AbsolutePathFilterIterator extends PathFilterIterator
{
    /**
     * @inheritDoc
     */
    public function accept()
    {
        $filename = $this->current()->getPathname();

        if (DIRECTORY_SEPARATOR === '\\') {
            $filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);
        }

        return $this->isAccepted($filename);
    }
}
