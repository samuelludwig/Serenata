<?php

namespace PhpIntegrator\Indexing;

use SplFileInfo;

use Ds\Queue;

use PhpIntegrator\Sockets\JsonRpcRequest;

/**
 * Indexes directories by generating one or more file index requests for each encountered file.
 */
class DirectoryIndexRequestDemuxer
{
    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var DirectoryIndexableFileIteratorFactory
     */
    private $directoryIndexableFileIteratorFactory;

    /**
     * @param Queue $queue
     * @param DirectoryIndexableFileIteratorFactory $directoryIndexableFileIteratorFactory
     */
    public function __construct(
        Queue $queue,
        DirectoryIndexableFileIteratorFactory $directoryIndexableFileIteratorFactory
    ) {
        $this->queue = $queue;
        $this->directoryIndexableFileIteratorFactory = $directoryIndexableFileIteratorFactory;
    }

    /**
     * @param string[] $paths
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     * @param int|null $originatingRequestId
     */
    public function index(
        array $paths,
        array $extensionsToIndex,
        array $globsToExclude,
        ?int $originatingRequestId
    ): void {
        $iterator = $this->directoryIndexableFileIteratorFactory->create($paths, $extensionsToIndex, $globsToExclude);

        $totalItems = iterator_count($iterator);

        $i = 1;

        foreach ($iterator as $fileInfo) {
            $this->queueIndexRequest($fileInfo, $extensionsToIndex, $globsToExclude);

            if ($originatingRequestId !== null) {
                $this->queueProgressRequest($originatingRequestId, $i++, $totalItems);
            }
        }
    }

    /**
     * @param SplFileInfo $fileInfo
     * @param string[]    $extensionsToIndex
     * @param string[]    $globsToExclude
     */
    protected function queueIndexRequest(SplFileInfo $fileInfo, array $extensionsToIndex, array $globsToExclude): void
    {
        $request = new JsonRpcRequest(null, 'reindex', [
            'source'    => $fileInfo->getPathname(),
            'exclude'   => $globsToExclude,
            'extension' => $extensionsToIndex
        ]);

        $this->queue->push($request);
    }

    /**
     * @param int $originatingRequestId
     * @param int $index
     * @param int $total
     */
    protected function queueProgressRequest(int $originatingRequestId, int $index, int $total): void
    {
        $request = new JsonRpcRequest(null, 'reindexProgress', [
            'requestId' => $originatingRequestId,
            'index'     => $index,
            'total'     => $total,
            'progress'  => ($index / $total) * 100
        ]);

        $this->queue->push($request);
    }
}
