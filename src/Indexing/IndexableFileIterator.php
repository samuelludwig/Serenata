<?php

namespace Serenata\Indexing;

use Iterator;
use Generator;
use SplFileInfo;
use IteratorIterator;
use IteratorAggregate;

use Symfony\Component\Finder\Finder;

/**
 * Iterator that iterates all indexable files for a path.
 *
 * @implements IteratorAggregate<SplFileInfo>
 */
final class IndexableFileIterator implements IteratorAggregate
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var string[]
     */
    private $extensionsToIndex;

    /**
     * @var string[]
     */
    private $globsToExclude;

    /**
     * @param string   $uri
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     */
    public function __construct(string $uri, array $extensionsToIndex, array $globsToExclude = [])
    {
        $this->uri = $uri;
        $this->extensionsToIndex = $extensionsToIndex;
        $this->globsToExclude = $globsToExclude;
    }

    /**
     * @return Iterator<SplFileInfo>
     */
    public function getIterator(): Iterator
    {
        return new IteratorIterator($this->iterate($this->uri));
    }

    /**
     * @param string $uri
     *
     * @return Generator<SplFileInfo>
     */
    private function iterate(string $uri): Generator
    {
        if ($this->extensionsToIndex === []) {
            return;
        }

        if (is_file($uri)) {
            yield from $this->iterateOverFileUri($uri);
        } else {
            yield from $this->iterateOverDirectoryUri($uri);
        }
    }

    /**
     * @param string $uri
     *
     * @return Generator<SplFileInfo>
     */
    private function iterateOverDirectoryUri(string $uri): Generator
    {
        $finder = $this->prepareFinder();
        $finder->in($uri);

        $iterator = new Iterating\AbsolutePathFilterIterator($finder->getIterator(), [], $this->globsToExclude);

        // $iterator = $this->fixUpUriEncoding($iterator);

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                yield $item;
            }
        }
    }

    /**
     * @param string $uri
     *
     * @return Generator<SplFileInfo>
     */
    private function iterateOverFileUri(string $uri): Generator
    {
        $finder = $this->prepareFinder();

        // We're only checking a file to see if it matches exclusion patterns and such. We use the parent directory
        // above in order to profit from Symfony finder, but we don't want to be recursing folders inside that
        // directory to avoid a performance hit.
        $finder->in(dirname($uri));
        $finder->depth('< 1');

        $iterator = new Iterating\AbsolutePathFilterIterator($finder->getIterator(), [], $this->globsToExclude);

        // $iterator = $this->fixUpUriEncoding($iterator);

        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getFilename() === basename($uri)) {
                // We scan the parent folder for files, see above. Breaking avoids scanning other files and,
                // possibly, folders recursively.
                yield $item;

                return;
            }
        }
    }

    // /**
    //  * Fixes up percentage encoding in URIs.
    //  *
    //  * @see https://gitlab.com/Serenata/Serenata/issues/278.
    //  *
    //  * @param Iterator $iterator
    //  *
    //  * @return Generator
    //  */
    // private function fixUpUriEncoding(Iterator $iterator): Generator
    // {
    //     // NOTE: This fixes encoding with URI, but then PHP's stream wrappers for file:// don't pick up these (valid)
    //     // paths anymore and all file functions start failing.
    //     $pathParts = explode(DIRECTORY_SEPARATOR, $item->getPathname());

    //     $protocol = array_shift($pathParts);

    //     $pathParts = array_map('rawurlencode', $pathParts);

    //     array_unshift($pathParts, $protocol);

    //     yield new SplFileInfo(implode(DIRECTORY_SEPARATOR, $pathParts));
    // }

    /**
     * @return Finder
     */
    private function prepareFinder(): Finder
    {
        /** @var string[] $globsToAdhereTo */
        $globsToAdhereTo = array_map(function (string $extension): string {
            return '/\.' . $extension . '$/';
        }, $this->extensionsToIndex);

        $finder = new Finder();
        $finder
            ->ignoreUnreadableDirs(true)
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->followLinks()
            ->name($globsToAdhereTo);

        return $finder;
    }
}
