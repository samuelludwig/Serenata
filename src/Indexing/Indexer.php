<?php

namespace Serenata\Indexing;

use UnexpectedValueException;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcMessageSenderInterface;

use Serenata\Utility\TextDocumentItem;
use Serenata\Utility\SourceCodeStreamReader;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * Indexes directories and files.
 */
final class Indexer implements IndexerInterface, EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @var DirectoryIndexRequestDemuxer
     */
    private $directoryIndexRequestDemuxer;

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
     * @param FileIndexerInterface         $fileIndexer
     * @param DirectoryIndexRequestDemuxer $directoryIndexRequestDemuxer
     * @param PathNormalizer               $pathNormalizer
     * @param SourceCodeStreamReader       $sourceCodeStreamReader
     * @param TextDocumentContentRegistry  $textDocumentContentRegistry
     * @param ActiveWorkspaceManager       $activeWorkspaceManager
     */
    public function __construct(
        FileIndexerInterface $fileIndexer,
        DirectoryIndexRequestDemuxer $directoryIndexRequestDemuxer,
        PathNormalizer $pathNormalizer,
        SourceCodeStreamReader $sourceCodeStreamReader,
        TextDocumentContentRegistry $textDocumentContentRegistry,
        ActiveWorkspaceManager $activeWorkspaceManager
    ) {
        $this->fileIndexer = $fileIndexer;
        $this->directoryIndexRequestDemuxer = $directoryIndexRequestDemuxer;
        $this->pathNormalizer = $pathNormalizer;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
        $this->activeWorkspaceManager = $activeWorkspaceManager;
    }

    /**
     * @inheritDoc
     */
    public function index(
        string $uri,
        bool $useLatestState,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): ExtendedPromiseInterface {
        $workspace = $this->activeWorkspaceManager->getActiveWorkspace();

        if ($workspace === null) {
            throw new UnexpectedValueException(
                'Cannot handle file change event when there is no active workspace, did you send an initialize ' .
                'request first?'
            );
        }

        $uri = $this->pathNormalizer->normalize($uri);

        if (is_file($uri)) {
            return $this->indexFile(
                $uri,
                $workspace->getConfiguration()->getFileExtensions(),
                $workspace->getConfiguration()->getExcludedPathExpressions(),
                $useLatestState
            );
        }

        return $this->indexDirectory(
            $uri,
            $workspace->getConfiguration()->getFileExtensions(),
            $workspace->getConfiguration()->getExcludedPathExpressions(),
            $jsonRpcMessageSender
        );
    }

    /**
     * @param string                         $uri
     * @param string[]                       $extensionsToIndex
     * @param string[]                       $globsToExclude
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     *
     * @return ExtendedPromiseInterface ExtendedPromiseInterface<null>
     */
    private function indexDirectory(
        string $uri,
        array $extensionsToIndex,
        array $globsToExclude,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): ExtendedPromiseInterface {
        return $this->directoryIndexRequestDemuxer->index(
            $uri,
            $extensionsToIndex,
            $globsToExclude,
            $jsonRpcMessageSender
        );
    }

    /**
     * @param string   $uri
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     * @param bool     $useLatestState
     *
     * @return ExtendedPromiseInterface ExtendedPromiseInterface<bool>
     */
    private function indexFile(
        string $uri,
        array $extensionsToIndex,
        array $globsToExclude,
        bool $useLatestState
    ): ExtendedPromiseInterface {
        if (!$this->isFileAllowed($uri, $extensionsToIndex, $globsToExclude)) {
            $deferred = new Deferred();
            $deferred->resolve(false);

            return $deferred->promise();
        } elseif ($useLatestState) {
            $code = $this->textDocumentContentRegistry->get($uri);
        } else {
            try {
                $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($uri);
            } catch (UnexpectedValueException $e) {
                // Skip files that we can't read.
                $deferred = new Deferred();
                $deferred->resolve(false);

                return $deferred->promise();
            }
        }

        try {
            $promise = $this->fileIndexer->index(new TextDocumentItem($uri, $code))->then(function (): bool {
                $this->emit(IndexingEventName::INDEXING_SUCCEEDED_EVENT);

                return true;
            });

            assert($promise instanceof ExtendedPromiseInterface);

            return $promise;
        } catch (IndexingFailedException $e) {
            $deferred = new Deferred();
            $deferred->resolve(false);

            return $deferred->promise();
        }
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
