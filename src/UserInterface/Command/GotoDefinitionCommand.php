<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\GotoDefinition\DefinitionLocator;
use PhpIntegrator\GotoDefinition\GotoDefinitionResult;

use PhpIntegrator\Indexing\StorageInterface;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

use PhpIntegrator\Utility\SourceCodeHelpers;
use PhpIntegrator\Utility\SourceCodeStreamReader;

/**
 * Allows navigating to the definition of a structural element by returning the location of its definition.
 */
class GotoDefinitionCommand extends AbstractCommand
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
     * @param StorageInterface       $storage
     * @param DefinitionLocator $definitionLocator
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     */
    public function __construct(
        StorageInterface $storage,
        DefinitionLocator $definitionLocator,
        SourceCodeStreamReader $sourceCodeStreamReader
    ) {
        $this->storage = $storage;
        $this->definitionLocator = $definitionLocator;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
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
        return $this->definitionLocator->locate($this->storage->getFileByPath($filePath), $code, $offset);
    }
}
