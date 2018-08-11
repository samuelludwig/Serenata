<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\Typing\Deduction\ExpressionTypeDeducer;

use Serenata\Common\Position;

use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileIndexerInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\TextDocumentItem;
use Serenata\Utility\SourceCodeStreamReader;

/**
 * Allows deducing the types of an expression (e.g. a call chain, a simple string, ...).
 */
final class DeduceTypesCommand extends AbstractCommand
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
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @var ExpressionTypeDeducer
     */
    private $expressionTypeDeducer;

    /**
     * @param StorageInterface       $storage
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     * @param FileIndexerInterface   $fileIndexer
     * @param ExpressionTypeDeducer  $expressionTypeDeducer
     */
    public function __construct(
        StorageInterface $storage,
        SourceCodeStreamReader $sourceCodeStreamReader,
        FileIndexerInterface $fileIndexer,
        ExpressionTypeDeducer $expressionTypeDeducer
    ) {
        $this->storage = $storage;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->fileIndexer = $fileIndexer;
        $this->expressionTypeDeducer = $expressionTypeDeducer;
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

        $codeWithExpression = $code;

        if (isset($arguments['expression'])) {
            $codeWithExpression = $arguments['expression'];
        }

        $result = $this->deduceTypes(
            $arguments['uri'],
            $code,
            $codeWithExpression,
            $position,
            isset($arguments['ignore-last-element']) && $arguments['ignore-last-element']
        );

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $result);
    }

    /**
     * @param string   $uri
     * @param string   $code
     * @param string   $codeWithExpression
     * @param Position $position
     * @param bool     $ignoreLastElement
     *
     * @return array
     */
    public function deduceTypes(
        string $uri,
        string $code,
        string $codeWithExpression,
        Position $position,
        bool $ignoreLastElement
    ): array {
        // Not used (yet), but still throws an exception when file is not in index.
        $this->storage->getFileByPath($uri);

        // $this->fileIndexer->index($uri, $code);

        return $this->expressionTypeDeducer->deduce(
            new TextDocumentItem($uri, $code),
            $position,
            $codeWithExpression,
            $ignoreLastElement
        );
    }
}
