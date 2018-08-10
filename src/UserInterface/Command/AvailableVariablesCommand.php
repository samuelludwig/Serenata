<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\VariableScanner;

use Serenata\Common\Position;

use Serenata\Indexing\FileIndexerInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;
use Serenata\Utility\SourceCodeStreamReader;

/**
 * Command that shows information about the scopes at a specific position in a file.
 */
final class AvailableVariablesCommand extends AbstractCommand
{
    /**
     * @var VariableScanner
     */
    private $variableScanner;

    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @param VariableScanner        $variableScanner
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     * @param FileIndexerInterface   $fileIndexer
     */
    public function __construct(
        VariableScanner $variableScanner,
        SourceCodeStreamReader $sourceCodeStreamReader,
        FileIndexerInterface $fileIndexer
    ) {
        $this->variableScanner = $variableScanner;
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

        $code = null;

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } elseif (isset($arguments['file']) && $arguments['file']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        }

        $offset = $arguments['offset'];

        if (isset($arguments['charoffset']) && $arguments['charoffset'] === true) {
            $offset = $this->getByteOffsetFromCharacterOffset($offset, $code);
        }

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $this->getAvailableVariables(
            $arguments['file'],
            $code,
            $offset
        ));
    }

    /**
     * @param string $filePath
     * @param string $code
     * @param int    $offset
     *
     * @return array
     */
    public function getAvailableVariables(string $filePath, string $code, int $offset): array
    {
        // $this->fileIndexer->index($filePath, $code);

        return $this->variableScanner->getAvailableVariables(
            new TextDocumentItem($filePath, $code),
            Position::createFromByteOffset($offset, $code, PositionEncoding::VALUE)
        );
    }
}
