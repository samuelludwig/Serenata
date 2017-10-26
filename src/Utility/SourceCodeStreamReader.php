<?php

namespace PhpIntegrator\Utility;

use UnexpectedValueException;

use PhpIntegrator\Analysis\SourceCodeReading\FileSourceCodeReaderException;
use PhpIntegrator\Analysis\SourceCodeReading\FileSourceCodeFileReaderFactory;
use PhpIntegrator\Analysis\SourceCodeReading\FileSourceCodeStreamReaderFactory;

/**
 * Deals with reading (not analyzing or parsing) source code.
 */
class SourceCodeStreamReader
{
    /**
     * @var FileSourceCodeFileReaderFactory
     */
    private $fileSourceCodeFileReaderFactory;

    /**
     * @var FileSourceCodeStreamReaderFactory
     */
    private $fileSourceCodeStreamReaderFactory;

    /**
     * @var StreamInterface
     */
    private $stdinStream;

    /**
     * @param FileSourceCodeFileReaderFactory   $fileSourceCodeFileReaderFactory
     * @param FileSourceCodeStreamReaderFactory $fileSourceCodeStreamReaderFactory
     * @param StreamInterface                   $stdinStream
     */
    public function __construct(
        FileSourceCodeFileReaderFactory $fileSourceCodeFileReaderFactory,
        FileSourceCodeStreamReaderFactory $fileSourceCodeStreamReaderFactory,
        StreamInterface $stdinStream
    ) {
        $this->fileSourceCodeFileReaderFactory = $fileSourceCodeFileReaderFactory;
        $this->fileSourceCodeStreamReaderFactory = $fileSourceCodeStreamReaderFactory;
        $this->stdinStream = $stdinStream;
    }

    /**
     * Reads source code from STDIN. Note that this call is blocking as long as there is no input!
     *
     * @throws FileSourceCodeReaderException
     *
     * @return string
     */
    public function getSourceCodeFromStdin(): string
    {
        return $this->fileSourceCodeStreamReaderFactory->create($this->stdinStream->getHandle())->read();
    }

    /**
     * @param string|null $file
     *
     * @throws UnexpectedValueException if the file doesn't exist or it is unreadable.
     * @throws FileSourceCodeReaderException
     *
     * @return string
     */
    public function getSourceCodeFromFile(?string $file): string
    {
        if (!$file) {
            throw new UnexpectedValueException("The file {$file} does not exist!");
        }

        return $this->fileSourceCodeFileReaderFactory->create($file)->read();
    }
}
