<?php

namespace Serenata\UserInterface\Command;

use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileIndexerInterface;

use Serenata\SignatureHelp\SignatureHelp;
use Serenata\SignatureHelp\SignatureHelpRetriever;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\SourceCodeHelpers;
use Serenata\Utility\SourceCodeStreamReader;

/**
 * Allows fetching signature help (call tips) for a method or function call.
 */
final class SignatureHelpCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var SignatureHelpRetriever
     */
    private $signatureHelpRetriever;

    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @param StorageInterface       $storage
     * @param SignatureHelpRetriever $signatureHelpRetriever
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     * @param FileIndexerInterface   $fileIndexer
     */
    public function __construct(
        StorageInterface $storage,
        SignatureHelpRetriever $signatureHelpRetriever,
        SourceCodeStreamReader $sourceCodeStreamReader,
        FileIndexerInterface $fileIndexer
    ) {
        $this->storage = $storage;
        $this->signatureHelpRetriever = $signatureHelpRetriever;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->fileIndexer = $fileIndexer;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A --file must be supplied!');
        } elseif (!isset($arguments['offset'])) {
            throw new InvalidArgumentsException('An --offset must be supplied into the source code!');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        }

        $offset = $arguments['offset'];

        if (isset($arguments['charoffset']) && $arguments['charoffset'] == true) {
            $offset = SourceCodeHelpers::getByteOffsetFromCharacterOffset($offset, $code);
        }

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->signatureHelp($arguments['file'], $code, $offset)
        );
    }

    /**
     * @param string $filePath
     * @param string $code
     * @param int    $offset
     *
     * @return SignatureHelp
     */
    public function signatureHelp(string $filePath, string $code, int $offset): SignatureHelp
    {
        $file = $this->storage->getFileByPath($filePath);

        // $this->fileIndexer->index($filePath, $code);

        return $this->signatureHelpRetriever->get($file, $code, $offset);
    }
}
