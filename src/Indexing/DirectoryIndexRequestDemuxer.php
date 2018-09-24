<?php

namespace Serenata\Indexing;

use SplFileInfo;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

use Serenata\Utility\FileChangeType;

/**
 * Indexes directories by generating one or more file index requests for each encountered file.
 */
final class DirectoryIndexRequestDemuxer
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
     * @param string                         $uri
     * @param string[]                       $extensionsToIndex
     * @param string[]                       $globsToExclude
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param int|string|null                $originatingRequestId
     */
    public function index(
        string $uri,
        array $extensionsToIndex,
        array $globsToExclude,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        $originatingRequestId
    ): void {
        $iterator = $this->directoryIndexableFileIteratorFactory->create($uri, $extensionsToIndex, $globsToExclude);

        // Convert to array early so we don't walk through the iterators (and perform disk access) twice.
        $items = iterator_to_array($iterator);

        $totalItems = count($items);

        $i = 1;

        foreach ($items as $fileInfo) {
            $this->queueIndexRequest($fileInfo, $jsonRpcResponseSender);

            if ($originatingRequestId !== null) {
                $this->queueProgressRequest($originatingRequestId, $i++, $totalItems, $jsonRpcResponseSender);
            }
        }
    }

    /**
     * @param SplFileInfo                    $fileInfo
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     */
    private function queueIndexRequest(
        SplFileInfo $fileInfo,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender
    ): void {
        $request = new JsonRpcRequest(null, 'workspace/didChangeWatchedFiles', [
            'changes' => [
                [
                    'uri'  => $fileInfo->getPathname(),
                    'type' => FileChangeType::CHANGED,
                ],
            ],
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcResponseSender));
    }

    /**
     * @param int|string|null                $originatingRequestId
     * @param int                            $index
     * @param int                            $total
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     */
    private function queueProgressRequest(
        $originatingRequestId,
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
                'progress'  => ($index / $total) * 100,
            ]),
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcResponseSender));
    }
}
