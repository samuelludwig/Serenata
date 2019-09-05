<?php

namespace Serenata\Utility;

/**
 * Base class for resource streams.
 */
abstract class AbstractResourceStream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $handle;

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function set(string $contents): void
    {
        ftruncate($this->getHandle(), 0);
        fwrite($this->getHandle(), $contents);
        rewind($this->getHandle());
    }

    /**
     * @inheritDoc
     */
    public function getHandle()
    {
        if ($this->handle === null) {
            $this->handle = $this->createHandle();
        }

        return $this->handle;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    /**
     * @return resource
     */
    abstract protected function createHandle();
}
