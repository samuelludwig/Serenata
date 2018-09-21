<?php

namespace Serenata\UserInterface\Command;

use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileIndexerInterface;

use Serenata\Linting\Linter;
use Serenata\Linting\PublishDiagnosticsParams;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\SourceCodeStreamReader;

/**
 * Command that lints a file for various problems.
 */
final class LintCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var Linter
     */
    private $linter;

    /**
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @param StorageInterface       $storage
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     * @param Linter                 $linter
     * @param FileIndexerInterface   $fileIndexer
     */
    public function __construct(
        StorageInterface $storage,
        SourceCodeStreamReader $sourceCodeStreamReader,
        Linter $linter,
        FileIndexerInterface $fileIndexer
    ) {
        $this->storage = $storage;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->linter = $linter;
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
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['uri']);
        }

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->lint($arguments['uri'], $code)
        );
    }

    /**
     * @param string $uri
     * @param string $code
     *
     * @return PublishDiagnosticsParams
     */
    public function lint(string $uri, string $code): PublishDiagnosticsParams
    {
        // Not used (yet), but still throws an exception when file is not in index.
        $this->storage->getFileByUri($uri);

        // $this->fileIndexer->index($uri, $code);

        return new PublishDiagnosticsParams($uri, $this->linter->lint($code));
    }
}
