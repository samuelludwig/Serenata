<?php

namespace Serenata\Indexing;

use UnexpectedValueException;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

use Serenata\Utility\TextDocumentItem;
use Serenata\Utility\SourceCodeStreamReader;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * Indexes directories and files.
 */
final class Indexer implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var JsonRpcQueue
     */
    private $queue;

    /**
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @var DirectoryIndexRequestDemuxer
     */
    private $directoryIndexRequestDemuxer;

    /**
     * @var IndexFilePruner
     */
    private $indexFilePruner;

    /**
     * @var PathNormalizer
     */
    private $pathNormalizer;

    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @param JsonRpcQueue                 $queue
     * @param FileIndexerInterface         $fileIndexer
     * @param DirectoryIndexRequestDemuxer $directoryIndexRequestDemuxer
     * @param IndexFilePruner              $indexFilePruner
     * @param PathNormalizer               $pathNormalizer
     * @param SourceCodeStreamReader       $sourceCodeStreamReader
     * @param TextDocumentContentRegistry  $textDocumentContentRegistry
     * @param ActiveWorkspaceManager       $activeWorkspaceManager
     */
    public function __construct(
        JsonRpcQueue $queue,
        FileIndexerInterface $fileIndexer,
        DirectoryIndexRequestDemuxer $directoryIndexRequestDemuxer,
        IndexFilePruner $indexFilePruner,
        PathNormalizer $pathNormalizer,
        SourceCodeStreamReader $sourceCodeStreamReader,
        TextDocumentContentRegistry $textDocumentContentRegistry,
        ActiveWorkspaceManager $activeWorkspaceManager
    ) {
        $this->queue = $queue;
        $this->fileIndexer = $fileIndexer;
        $this->directoryIndexRequestDemuxer = $directoryIndexRequestDemuxer;
        $this->indexFilePruner = $indexFilePruner;
        $this->pathNormalizer = $pathNormalizer;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
        $this->activeWorkspaceManager = $activeWorkspaceManager;
    }

    /**
     * @param string                         $uri
     * @param bool                           $useLatestState
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param JsonRpcResponse|null           $responseToSendOnCompletion
     *
     * @return bool
     */
    public function index(
        string $uri,
        bool $useLatestState,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        ?JsonRpcResponse $responseToSendOnCompletion = null
    ): bool {
        $workspace = $this->activeWorkspaceManager->getActiveWorkspace();

        if (!$workspace) {
            throw new UnexpectedValueException(
                'Cannot handle file change event when there is no active workspace, did you send an initialize ' .
                'request first?'
            );
        }

        $uri = $this->pathNormalizer->normalize($uri);

        if (is_dir($uri)) {
            $this->indexDirectory(
                $uri,
                $workspace->getConfiguration()->getFileExtensions(),
                $workspace->getConfiguration()->getExcludedPathExpressions(),
                $jsonRpcResponseSender,
                $responseToSendOnCompletion ? $responseToSendOnCompletion->getId() : null
            );

            $this->indexFilePruner->prune();
        } elseif (is_file($uri)) {
            $this->indexFile(
                $uri,
                $workspace->getConfiguration()->getFileExtensions(),
                $workspace->getConfiguration()->getExcludedPathExpressions(),
                $useLatestState
            );
        }

        if ($responseToSendOnCompletion === null) {
            return true;
        }

        // As a directory index request is demuxed into multiple file index requests, the response for the original
        // request may not be sent until all individual file index requests have been handled. This command will
        // send that "finish" response when executed.
        //
        // This request will not be queued for file reindex requests that are the result of the demuxing as those
        // don't have an originating request ID.
        $delayedIndexFinishRequest = new JsonRpcRequest(null, 'echoResponse', [
            'response' => $responseToSendOnCompletion,
        ]);

        $this->queue->push(new JsonRpcQueueItem($delayedIndexFinishRequest, $jsonRpcResponseSender));

        return true;
    }

    /**
     * @param string                         $uri
     * @param string[]                       $extensionsToIndex
     * @param string[]                       $globsToExclude
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param int|string|null                $requestId
     */
    private function indexDirectory(
        string $uri,
        array $extensionsToIndex,
        array $globsToExclude,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        $requestId
    ): void {
        $this->directoryIndexRequestDemuxer->index(
            $uri,
            $extensionsToIndex,
            $globsToExclude,
            $jsonRpcResponseSender,
            $requestId
        );
    }

    /**
     * @param string   $uri
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     * @param bool     $useLatestState
     *
     * @return bool
     */
    private function indexFile(
        string $uri,
        array $extensionsToIndex,
        array $globsToExclude,
        bool $useLatestState
    ): bool {
        if (!$this->isFileAllowed($uri, $extensionsToIndex, $globsToExclude)) {
            return false;
        } elseif ($useLatestState) {
            $code = $this->textDocumentContentRegistry->get($uri);
        } else {
            try {
                $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($uri);
            } catch (UnexpectedValueException $e) {
                return false; // Skip files that we can't read.
            }
        }

        try {
            $this->fileIndexer->index(new TextDocumentItem($uri, $code));
        } catch (IndexingFailedException $e) {
            return false;
        }

        $this->emit(IndexingEventName::INDEXING_SUCCEEDED_EVENT);

        return true;
    }

    /**
     * @param string   $path
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     *
     * @return bool
     */
    private function isFileAllowed(string $path, array $extensionsToIndex, array $globsToExclude): bool
    {
        $iterator = new IndexableFileIterator($path, $extensionsToIndex, $globsToExclude);

        return iterator_count($iterator) > 0;
    }
}
