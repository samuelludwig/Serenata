<?php

namespace Serenata\Indexing;

use UnexpectedValueException;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;

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
    public function index(string $uri, bool $useLatestState, JsonRpcMessageSenderInterface $jsonRpcMessageSender): bool
    {
        $workspace = $this->activeWorkspaceManager->getActiveWorkspace();

        if ($workspace === null) {
            throw new UnexpectedValueException(
                'Cannot handle file change event when there is no active workspace, did you send an initialize ' .
                'request first?'
            );
        }

        $uri = $this->pathNormalizer->normalize($uri);

        $result = true;

        if (is_dir($uri)) {
            $this->indexDirectory(
                $uri,
                $workspace->getConfiguration()->getFileExtensions(),
                $workspace->getConfiguration()->getExcludedPathExpressions(),
                $jsonRpcMessageSender
            );
        } elseif (is_file($uri)) {
            $result = $this->indexFile(
                $uri,
                $workspace->getConfiguration()->getFileExtensions(),
                $workspace->getConfiguration()->getExcludedPathExpressions(),
                $useLatestState
            );
        }

        return $result;
    }

    /**
     * @param string                         $uri
     * @param string[]                       $extensionsToIndex
     * @param string[]                       $globsToExclude
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    private function indexDirectory(
        string $uri,
        array $extensionsToIndex,
        array $globsToExclude,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender
    ): void {
        $this->directoryIndexRequestDemuxer->index(
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
