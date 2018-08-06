<?php

namespace Serenata\UserInterface\Command;

use Serenata\Common\Position;

use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileIndexerInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Tooltips\TooltipResult;
use Serenata\Tooltips\TooltipProvider;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;
use Serenata\Utility\SourceCodeStreamReader;

/**
 * Command that fetches tooltip information for a specific location.
 */
final class TooltipCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var TooltipProvider
     */
    private $tooltipProvider;

    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @param StorageInterface       $storage
     * @param TooltipProvider        $tooltipProvider
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     * @param FileIndexerInterface   $fileIndexer
     */
    public function __construct(
        StorageInterface $storage,
        TooltipProvider $tooltipProvider,
        SourceCodeStreamReader $sourceCodeStreamReader,
        FileIndexerInterface $fileIndexer
    ) {
        $this->storage = $storage;
        $this->tooltipProvider = $tooltipProvider;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->fileIndexer = $fileIndexer;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A --file must be supplied!');
        } elseif (!isset($arguments['offset'])) {
            throw new InvalidArgumentsException('An --offset must be supplied into the source code!');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        }

        $offset = $arguments['offset'];

        if (isset($arguments['charoffset']) && $arguments['charoffset'] == true) {
            $offset = $this->getByteOffsetFromCharacterOffset($offset, $code);
        }

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->getTooltip($arguments['file'], $code, $offset)
        );
    }

    /**
     * @param string $filePath
     * @param string $code
     * @param int    $offset
     *
     * @return TooltipResult|null
     */
    public function getTooltip(string $filePath, string $code, int $offset): ?TooltipResult
    {
        // Not used (yet), but will validate if file exists in index.
        $file = $this->storage->getFileByPath($filePath);

        // $this->fileIndexer->index($filePath, $code);

        return $this->tooltipProvider->get(
            new TextDocumentItem($filePath, $code),
            Position::createFromByteOffset($offset, $code, PositionEncoding::VALUE)
        );
    }
}
