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
     * @var PathNormalizer
     */
    private $pathNormalizer;

    /**
     * @param JsonRpcQueue                          $queue
     * @param DirectoryIndexableFileIteratorFactory $directoryIndexableFileIteratorFactory
     */
    public function __construct(
        JsonRpcQueue $queue,
        DirectoryIndexableFileIteratorFactory $directoryIndexableFileIteratorFactory,
        PathNormalizer $pathNormalizer
    ) {
        $this->queue = $queue;
        $this->directoryIndexableFileIteratorFactory = $directoryIndexableFileIteratorFactory;
        $this->pathNormalizer = $pathNormalizer;
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

        $token = uniqid('IndexingProgress');

        $this->queueWorkDoneProgressCreateTokenRequest($token, $jsonRpcMessageSender);
        $this->queueWorkDoneProgressBeginNotification($token, $uri, $jsonRpcMessageSender);

        foreach ($items as $fileInfo) {
            $folderUri = $this->pathNormalizer->normalize($fileInfo->getPathname());

            $this->queueIndexRequest($fileInfo, $jsonRpcMessageSender);

            $this->queueWorkDoneProgressReportNotification(
                $token,
                $folderUri,
                $i,
                $totalItems,
                $jsonRpcMessageSender
            );

            ++$i;
        }

        $this->queueWorkDoneProgressEndNotification($token, $uri, $jsonRpcMessageSender);
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
                'uri'  => $this->pathNormalizer->normalize($fileInfo->getPathname()),
            ],
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
    }

    /**
     * @param string                        $token
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    private function queueWorkDoneProgressCreateTokenRequest(
        string $token,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void {
        $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
            'message' => new JsonRpcRequest(uniqid('IndexingTokenCreation'), 'window/workDoneProgress/create', [
                'token' => $token,
            ]),
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
    }

    /**
     * @param string                        $token
     * @param string                        $folderUri
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    private function queueWorkDoneProgressBeginNotification(
        string $token,
        string $folderUri,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void {
        $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
            'message' => new JsonRpcRequest(null, '$/progress', [
                'token' => $token,
                'value' => [
                    'kind'        => 'begin',
                    'title'       => 'Indexing',
                    'cancellable' => false,
                    'message'     => $folderUri,
                    'percentage'  => 0.00,
                ],
            ]),
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
    }

    /**
     * @param string                        $token
     * @param string                        $folderUri
     * @param int                           $index
     * @param int                           $total
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    private function queueWorkDoneProgressReportNotification(
        string $token,
        string $folderUri,
        int $index,
        int $total,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void {
        $progressPercentage = ($index / $total) * 100;

        $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
            'message' => new JsonRpcRequest(null, '$/progress', [
                'token' => $token,
                'value' => [
                    'kind'        => 'report',
                    'cancellable' => false,
                    'message'     => $folderUri,
                    'percentage'  => $progressPercentage,
                ],
            ]),
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
    }

    /**
     * @param string                        $token
     * @param string                        $folderUri
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    private function queueWorkDoneProgressEndNotification(
        string $token,
        string $folderUri,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void {
        $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
            'message' => new JsonRpcRequest(null, '$/progress', [
                'token' => $token,
                'value' => [
                    'kind'    => 'end',
                    'message' => "Indexing " . $folderUri . ' completed',
                ],
            ]),
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
    }
}
