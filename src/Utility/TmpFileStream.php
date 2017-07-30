<?php

namespace PhpIntegrator\Utility;

use RuntimeException;

/**
 * Represents a stream to a temporary file.
 */
class TmpFileStream extends AbstractResourceStream
{
    /**
     * @inheritDoc
     */
    protected function createHandle()
    {
        return tmpfile();
    }
}
