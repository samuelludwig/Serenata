<?php

namespace Serenata\Utility;

use UnexpectedValueException;

use Serenata\Analysis\SourceCodeReading\FileSourceCodeReaderException;
use Serenata\Analysis\SourceCodeReading\FileSourceCodeFileReaderFactory;
use Serenata\Analysis\SourceCodeReading\FileSourceCodeStreamReaderFactory;

/**
 * Deals with reading (not analyzing or parsing) source code.
 */
final class SourceCodeStreamReader
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
     * @throws FileSourceCodeReaderException
     *
     * @return string
     */
    public function getSourceCodeFromFile(?string $file): string
    {
        if ($file === null) {
            throw new FileSourceCodeReaderException("File {$file} does not exist");
        }

        return $this->fileSourceCodeFileReaderFactory->create($file)->read();
    }
}
