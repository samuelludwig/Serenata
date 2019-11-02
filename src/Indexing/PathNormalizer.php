<?php

namespace Serenata\Indexing;

/**
 * Normalizes paths.
 */
final class PathNormalizer
{
    /**
     * @param string $path
     *
     * @return string
     */
    public function normalize(string $path): string
    {
        return $this->normalizeSlashes($this->resolveHomeDirectoryTilde($path));
    }

    /**
     * Return a version of the path variable with normalized slashes.
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizeSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function resolveHomeDirectoryTilde(string $path): string
    {
        if (substr($path, 0, 1) === '~' && isset($_SERVER['HOME'])) {
            return substr_replace($path, $_SERVER['HOME'], 0, 1);
        }

        return $path;
    }
}
