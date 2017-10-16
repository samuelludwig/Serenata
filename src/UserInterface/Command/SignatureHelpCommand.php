<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\FileIndexerInterface;

use PhpIntegrator\SignatureHelp\SignatureHelp;
use PhpIntegrator\SignatureHelp\SignatureHelpRetriever;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

use PhpIntegrator\Utility\SourceCodeHelpers;
use PhpIntegrator\Utility\SourceCodeStreamReader;

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

        // TODO: At the time of writing I could no longer reproduce the issue. If I can reproduce it in the future and
        // come back to this branch, the same needs to be implemented for all other commands; they also need to index
        // STDIN immediately. For some commands this will mean that a file path is now required, and the clients will
        // always need to send a file (BC break). In the long term, this could also mean that individual index events
        // are no longer required. See also https://gitlab.com/php-integrator/core/issues/126
        $this->fileIndexer->index($filePath, $code);

        return $this->signatureHelpRetriever->get($file, $code, $offset);
    }
}
