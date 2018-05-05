<?php

namespace Serenata\Utility;

use RuntimeException;

/**
 * Interface for classes representing streams.
 */
interface StreamInterface
{
    /**
     * @param string $contents
     */
    public function set(string $contents): void;

    /**
     * @return resource
     */
    public function getHandle();

    /**
     * @return void
     */
    public function close(): void;
}
