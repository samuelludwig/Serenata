<?php

namespace Serenata\UserInterface\Command;

use Serenata\Common\Position;

use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileIndexerInterface;

use Serenata\SignatureHelp\SignatureHelp;
use Serenata\SignatureHelp\SignatureHelpRetriever;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;
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

        if (!isset($arguments['uri'])) {
            throw new InvalidArgumentsException('"uri" must be supplied');
        } elseif (!isset($arguments['position'])) {
            throw new InvalidArgumentsException('"position" into the source must be supplied');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['uri']);
        }

        $position = new Position($arguments['position']['line'], $arguments['position']['character']);

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->signatureHelp($arguments['uri'], $code, $position)
        );
    }

    /**
     * @param string   $uri
     * @param string   $code
     * @param Position $position
     *
     * @return SignatureHelp
     */
    public function signatureHelp(string $uri, string $code, Position $position): SignatureHelp
    {
        // Not used (yet), but still throws an exception when file is not in index.
        $this->storage->getFileByPath($uri);

        // $this->fileIndexer->index($uri, $code);

        return $this->signatureHelpRetriever->get(new TextDocumentItem($uri, $code), $position);
    }
}
