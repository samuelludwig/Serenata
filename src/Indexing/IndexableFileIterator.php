<?php

namespace Serenata\Indexing;

use Iterator;
use Generator;
use IteratorIterator;
use IteratorAggregate;

use Symfony\Component\Finder\Finder;

/**
 * Iterator that iterates all indexable files for a path.
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
     * @inheritDoc
     */
    public function getIterator(): Iterator
    {
        return new IteratorIterator($this->iterate($this->uri));
    }

    /**
     * @param string $uri
     *
     * @return Generator
     */
    private function iterate(string $uri): Generator
    {
        if ($this->extensionsToIndex === []) {
            return;
        }

        $isFile = is_file($uri);

        /** @var string[] $globsToAdhereTo */
        $globsToAdhereTo = array_map(function (string $extension) {
            return '*.' . $extension;
        }, $this->extensionsToIndex);

        $finder = new Finder();
        $finder
            // For single URIs, move up to parent folder so we can follow the same flow and pattern matching.
            ->in($isFile ? dirname($uri) : $uri)
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->followLinks()
            ->name($globsToAdhereTo)
            ->notName($this->globsToExclude);

        if ($isFile) {
            $finder->name(basename($uri));
        }

        foreach ($finder as $item) {
            if ($item->isDir()) {
                yield from $this->iterate($item->getPathname());
            } elseif ($item->isFile()) {
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

                yield $item;
            }
        }
    }
}
