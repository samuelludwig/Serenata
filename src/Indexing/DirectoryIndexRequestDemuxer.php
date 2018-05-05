<?php

namespace Serenata\Indexing;

use SplFileInfo;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

/**
 * Indexes directories by generating one or more file index requests for each encountered file.
 */
class DirectoryIndexRequestDemuxer
{
    /**
     * @var JsonRpcQueue
     */
    private $queue;

    /**
     * @var DirectoryIndexableFileIteratorFactory
     */
    private $directoryIndexableFileIteratorFactory;

    /**
     * @param JsonRpcQueue                          $queue
     * @param DirectoryIndexableFileIteratorFactory $directoryIndexableFileIteratorFactory
     */
    public function __construct(
        JsonRpcQueue $queue,
        DirectoryIndexableFileIteratorFactory $directoryIndexableFileIteratorFactory
    ) {
        $this->queue = $queue;
        $this->directoryIndexableFileIteratorFactory = $directoryIndexableFileIteratorFactory;
    }

    /**
     * @param string[]                       $paths
     * @param string[]                       $extensionsToIndex
     * @param string[]                       $globsToExclude
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param int|null                       $originatingRequestId
     */
    public function index(
        array $paths,
        array $extensionsToIndex,
        array $globsToExclude,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        ?int $originatingRequestId
    ): void {
        $iterator = $this->directoryIndexableFileIteratorFactory->create($paths, $extensionsToIndex, $globsToExclude);

        // Convert to array early so we don't walk through the iterators (and perform disk access) twice.
        $items = iterator_to_array($iterator);

        $totalItems = count($items);

        $i = 1;

        foreach ($items as $fileInfo) {
            $this->queueIndexRequest($fileInfo, $extensionsToIndex, $globsToExclude, $jsonRpcResponseSender);

            if ($originatingRequestId !== null) {
                $this->queueProgressRequest($originatingRequestId, $i++, $totalItems, $jsonRpcResponseSender);
            }
        }
    }

    /**
     * @param SplFileInfo                    $fileInfo
     * @param string[]                       $extensionsToIndex
     * @param string[]                       $globsToExclude
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     */
    private function queueIndexRequest(
        SplFileInfo $fileInfo,
        array $extensionsToIndex,
        array $globsToExclude,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender
    ): void {
        $request = new JsonRpcRequest(null, 'reindex', [
            'source'    => [$fileInfo->getPathname()],
            'exclude'   => $globsToExclude,
            'extension' => $extensionsToIndex
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcResponseSender));
    }

    /**
     * @param int                            $originatingRequestId
     * @param int                            $index
     * @param int                            $total
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     */
    private function queueProgressRequest(
        int $originatingRequestId,
        int $index,
        int $total,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender
    ): void {
        $request = new JsonRpcRequest(null, 'echoResponse', [
            'response' => new JsonRpcResponse(null, [
                'type'      => 'reindexProgressInformation',
                'requestId' => $originatingRequestId,
                'index'     => $index,
                'total'     => $total,
                'progress'  => ($index / $total) * 100
            ])
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcResponseSender));
    }
}
