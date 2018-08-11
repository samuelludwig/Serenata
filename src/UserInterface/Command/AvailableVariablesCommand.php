<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\VariableScanner;

use Serenata\Common\Position;

use Serenata\Indexing\FileIndexerInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

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

        if (!isset($arguments['uri'])) {
            throw new InvalidArgumentsException('"uri" must be supplied');
        } elseif (!isset($arguments['position'])) {
            throw new InvalidArgumentsException('"position" into the source must be supplied');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['uri']);
        }

        $position = new Position($arguments['position']['line'], $arguments['position']['character']);

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $this->getAvailableVariables(
            $arguments['uri'],
            $code,
            $position
        ));
    }

    /**
     * @param string   $uri
     * @param string   $code
     * @param Position $position
     *
     * @return array
     */
    public function getAvailableVariables(string $uri, string $code, Position $position): array
    {
        // $this->fileIndexer->index($uri, $code);

        return $this->variableScanner->getAvailableVariables(new TextDocumentItem($uri, $code), $position);
    }
}
