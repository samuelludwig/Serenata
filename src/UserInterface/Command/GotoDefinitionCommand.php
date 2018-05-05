<?php

namespace Serenata\UserInterface\Command;

use Serenata\GotoDefinition\DefinitionLocator;
use Serenata\GotoDefinition\GotoDefinitionResult;

use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileIndexerInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\SourceCodeHelpers;
use Serenata\Utility\SourceCodeStreamReader;

/**
 * Allows navigating to the definition of a structural element by returning the location of its definition.
 */
final class GotoDefinitionCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DefinitionLocator
     */
    private $definitionLocator;

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
     * @param DefinitionLocator      $definitionLocator
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     * @param FileIndexerInterface   $fileIndexer
     */
    public function __construct(
        StorageInterface $storage,
        DefinitionLocator $definitionLocator,
        SourceCodeStreamReader $sourceCodeStreamReader,
        FileIndexerInterface $fileIndexer
    ) {
        $this->storage = $storage;
        $this->definitionLocator = $definitionLocator;
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
            $offset = SourceCodeHelpers::getByteOffsetFromCharacterOffset($offset, $code);
        }

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->gotoDefinition($arguments['file'], $code, $offset)
        );
    }

    /**
     * @param string $filePath
     * @param string $code
     * @param int    $offset
     *
     * @return GotoDefinitionResult|null
     */
    public function gotoDefinition(string $filePath, string $code, int $offset): ?GotoDefinitionResult
    {
        $file = $this->storage->getFileByPath($filePath);

        // $this->fileIndexer->index($filePath, $code);

        return $this->definitionLocator->locate($file, $code, $offset);
    }
}
