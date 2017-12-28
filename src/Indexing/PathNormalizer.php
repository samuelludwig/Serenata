<?php

namespace PhpIntegrator\Indexing;

use PhpIntegrator\Indexing\Indexer;

use PhpIntegrator\Sockets\JsonRpcRequest;

/**
 * Normalizes paths.
 */
class PathNormalizer
{
    /**
     * @param string $path
     *
     * @return string
     */
    public function normalize(string $path): string
    {
        return $this->resolveHomeDirectoryTilde($path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function resolveHomeDirectoryTilde(string $path): string
    {
        if (substr($path, 0, 1) === '~' && isset($_SERVER['HOME'])) {
			return substr_replace($path, $_SERVER['HOME'], 0, 1);
		}

        return $path;
    }
}
