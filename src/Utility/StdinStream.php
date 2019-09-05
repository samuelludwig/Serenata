<?php

namespace Serenata\Utility;

/**
 * Represents the STDIN stream.
 */
final class StdinStream extends AbstractResourceStream
{
    /**
     * @inheritDoc
     */
    protected function createHandle()
    {
        $stream = fopen('php://memory', 'w+');

        assert($stream !== false);

        return $stream;
    }
}
