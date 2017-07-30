<?php

namespace PhpIntegrator\Utility;

use RuntimeException;

/**
 * Represents the STDIN stream.
 */
class StdinStream extends AbstractResourceStream
{
    /**
     * @inheritDoc
     */
    protected function createHandle()
    {
        return fopen('php://memory', 'w+');
    }
}
