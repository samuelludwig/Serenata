<?php

namespace Serenata\Indexing;

use UnexpectedValueException;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

use Serenata\Utility\TextDocumentItem;
use Serenata\Utility\SourceCodeStreamReader;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;

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
     * @var DirectoryIndexableFileIteratorFactory
     */
    private $directoryIndexableFileIteratorFactory;

    /**
     * @param JsonRpcQueue                          $queue
     * @param FileIndexerInterface                  $fileIndexer
     * @param DirectoryIndexRequestDemuxer          $directoryIndexRequestDemuxer
     * @param IndexFilePruner                       $indexFilePruner
     * @param PathNormalizer                        $pathNormalizer
     * @param SourceCodeStreamReader                $sourceCodeStreamReader
     * @param DirectoryIndexableFileIteratorFactory $directoryIndexableFileIteratorFactory
     */
    public function __construct(
        JsonRpcQueue $queue,
        FileIndexerInterface $fileIndexer,
        DirectoryIndexRequestDemuxer $directoryIndexRequestDemuxer,
        IndexFilePruner $indexFilePruner,
        PathNormalizer $pathNormalizer,
        SourceCodeStreamReader $sourceCodeStreamReader,
        DirectoryIndexableFileIteratorFactory $directoryIndexableFileIteratorFactory
    ) {
        $this->queue = $queue;
        $this->fileIndexer = $fileIndexer;
        $this->directoryIndexRequestDemuxer = $directoryIndexRequestDemuxer;
        $this->indexFilePruner = $indexFilePruner;
        $this->pathNormalizer = $pathNormalizer;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->directoryIndexableFileIteratorFactory = $directoryIndexableFileIteratorFactory;
    }

    /**
     * @param string[]                       $paths
     * @param string[]                       $extensionsToIndex
     * @param string[]                       $globsToExclude
     * @param bool                           $useStdin
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param int|string|null                $originatingRequestId
     *
     * @return bool
     */
    public function index(
        array $paths,
        array $extensionsToIndex,
        array $globsToExclude,
        bool $useStdin,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        $originatingRequestId = null
    ): bool {
        $paths = array_map(function (string $path) {
            return $this->pathNormalizer->normalize($path);
        }, $paths);

        $directories = array_filter($paths, function (string $path) {
            return is_dir($path);
        });

        $files = array_filter($paths, function (string $path) {
            return !is_dir($path);
        });

        $this->indexDirectories(
            $directories,
            $extensionsToIndex,
            $globsToExclude,
            $jsonRpcResponseSender,
            $originatingRequestId
        );

        foreach ($files as $path) {
            $this->indexFile($path, $extensionsToIndex, $globsToExclude, $useStdin);
        }

        if ($originatingRequestId === null) {
            return true;
        }

        if (!empty($directories)) {
            $this->indexFilePruner->prune();
        }

        // As a directory index request is demuxed into multiple file index requests, the response for the original
        // request may not be sent until all individual file index requests have been handled. This command will
        // send that "finish" response when executed.
        //
        // This request will not be queued for file reindex requests that are the result of the demuxing as those
        // don't have an originating request ID.
        $delayedIndexFinishRequest = new JsonRpcRequest(null, 'echoResponse', [
            'response' => new JsonRpcResponse($originatingRequestId, true),
        ]);

        $this->queue->push(new JsonRpcQueueItem($delayedIndexFinishRequest, $jsonRpcResponseSender));

        return true;
    }

    /**
     * @param string[]                       $paths
     * @param string[]                       $extensionsToIndex
     * @param string[]                       $globsToExclude
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param int|string|null                $requestId
     */
    private function indexDirectories(
        array $paths,
        array $extensionsToIndex,
        array $globsToExclude,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        $requestId
    ): void {
        if (count($paths) === 0) {
            return; // Optimization that skips expensive operations during demuxing, which stack.
        }

        $this->directoryIndexRequestDemuxer->index(
            $paths,
            $extensionsToIndex,
            $globsToExclude,
            $jsonRpcResponseSender,
            $requestId
        );
    }

    /**
     * @param string   $path
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     * @param bool     $useStdin
     *
     * @return bool
     */
    private function indexFile(string $path, array $extensionsToIndex, array $globsToExclude, bool $useStdin): bool
    {
        if (!$this->isFileAllowed($path, $extensionsToIndex, $globsToExclude)) {
            return false;
        } elseif ($useStdin) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            try {
                $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($path);
            } catch (UnexpectedValueException $e) {
                return false; // Skip files that we can't read.
            }
        }

        try {
            $this->fileIndexer->index(new TextDocumentItem($path, $code));
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
        $iterator = new IndexableFileIterator([$path], $extensionsToIndex, $globsToExclude);

        return !empty(iterator_to_array($iterator));
    }
}
