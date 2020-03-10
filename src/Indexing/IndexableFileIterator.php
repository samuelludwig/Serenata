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

        $isFile = is_file($uri);

        /** @var string[] $globsToAdhereTo */
        $globsToAdhereTo = array_map(function (string $extension): string {
            return '/\.' . $extension . '$/';
        }, $this->extensionsToIndex);

        $finder = new Finder();
        $finder
            // For single URIs, move up to parent folder so we can follow the same flow and pattern matching.
            ->in($isFile ? dirname($uri) : $uri)
            ->ignoreUnreadableDirs(true)
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->followLinks()
            ->name($globsToAdhereTo);

        $iterator = new Iterating\AbsolutePathFilterIterator($finder->getIterator(), [], $this->globsToExclude);

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                // NOTE: See https://gitlab.com/Serenata/Serenata/issues/278 . This fixes encoding with URI, but then
                // PHP's stream wrappers for file:// don't pick up these (valid) paths anymore and all file functions
                // start failing.
                // $pathParts = explode(DIRECTORY_SEPARATOR, $item->getPathname());
                //
                // $protocol = array_shift($pathParts);
                //
                // $pathParts = array_map('rawurlencode', $pathParts);
                //
                // array_unshift($pathParts, $protocol);
                //
                // yield new SplFileInfo(implode(DIRECTORY_SEPARATOR, $pathParts));

                if (!$isFile) {
                    yield $item;
                } elseif ($item->getFilename() === basename($uri)) {
                    // We scan the parent folder for files, see above. Breaking avoids scanning other files and,
                    // possibly, folders recursively.
                    yield $item;

                    break;
                }
            }
        }
    }
}
