<?php

namespace Serenata\Indexing;

use SplFileInfo;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

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
     * @param string                        $uri
     * @param string[]                      $extensionsToIndex
     * @param string[]                      $globsToExclude
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    public function index(
        string $uri,
        array $extensionsToIndex,
        array $globsToExclude,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void {
        $iterator = $this->directoryIndexableFileIteratorFactory->create($uri, $extensionsToIndex, $globsToExclude);

        // Convert to array early so we don't walk through the iterators (and perform disk access) twice.
        $items = iterator_to_array($iterator);

        $totalItems = count($items);

        $i = 1;

        foreach ($items as $fileInfo) {
            $this->queueIndexRequest($fileInfo, $jsonRpcMessageSender);

            $this->queueProgressRequest(
                $uri,
                $fileInfo->getPathname(),
                $i++,
                $totalItems,
                $jsonRpcMessageSender
            );
        }
    }

    /**
     * @param SplFileInfo                    $fileInfo
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    private function queueIndexRequest(
        SplFileInfo $fileInfo,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void {
        $request = new JsonRpcRequest(null, 'serenata/internal/index', [
            'textDocument' => [
                'uri'  => $fileInfo->getPathname(),
            ],
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
    }

    /**
     * @param string                        $folderUri
     * @param string                        $fileUri
     * @param int                           $index
     * @param int                           $total
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    private function queueProgressRequest(
        string $folderUri,
        string $fileUri,
        int $index,
        int $total,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void {
        $progressPercentage = ($index / $total) * 100;

        $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
            'message' => new JsonRpcRequest(null, 'serenata/didProgressIndexing', [
                'sequenceOfIndexedItem' => $index,
                'totalItemsToIndex'     => $total,
                'progressPercentage'    => $progressPercentage,
                'folderUri'             => $folderUri,
                'fileUri'               => $fileUri,

                'info' => "Indexing " . $folderUri . ' (' . number_format($progressPercentage, 2) . ' %)',
            ]),
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
    }
}
