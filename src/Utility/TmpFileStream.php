<?php

namespace Serenata\Utility;

/**
 * Represents a stream to a temporary file.
 */
final class TmpFileStream extends AbstractResourceStream
{
    /**
     * @inheritDoc
     */
    protected function createHandle()
    {
        $file = tmpfile();

        assert($file !== false);

        return $file;
    }
}
