<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Analysis\Typing\Deduction\ExpressionTypeDeducer;

use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\FileIndexerInterface;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

use PhpIntegrator\Utility\SourceCodeHelpers;
use PhpIntegrator\Utility\SourceCodeStreamReader;

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

        $codeWithExpression = $code;

        if (isset($arguments['expression'])) {
            $codeWithExpression = $arguments['expression'];
        }

        $result = $this->deduceTypes(
            $arguments['file'],
            $code,
            $codeWithExpression,
            $offset,
            isset($arguments['ignore-last-element']) && $arguments['ignore-last-element']
        );

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $result);
    }

    /**
     * @param string $filePath
     * @param string $code
     * @param string $codeWithExpression
     * @param int    $offset
     * @param bool   $ignoreLastElement
     *
     * @return array
     */
    public function deduceTypes(
        string $filePath,
        string $code,
        string $codeWithExpression,
        int $offset,
        bool $ignoreLastElement
    ): array {
        $file = $this->storage->getFileByPath($filePath);

        $this->fileIndexer->index($filePath, $code);

        return $this->expressionTypeDeducer->deduce(
            $file,
            $code,
            $offset,
            $codeWithExpression,
            $ignoreLastElement
        );
    }
}
