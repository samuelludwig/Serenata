<?php

namespace Serenata\UserInterface;

use LogicException;
use DomainException;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Generates an appropriate handler for a {@see JsonRpcQueueItem}
 */
final class JsonRpcQueueItemHandlerFactory implements JsonRpcQueueItemHandlerFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function create(string $method): JsonRpcQueueItemHandler\JsonRpcQueueItemHandlerInterface
    {
        $methodServiceNameMap = [
            'initialize'                              => 'initializeJsonRpcQueueItemHandler',
            'initialized'                             => 'initializedJsonRpcQueueItemHandler',
            'exit'                                    => 'exitJsonRpcQueueItemHandler',
            'workspace/didChangeWatchedFiles'         => 'didChangeWatchedFilesJsonRpcQueueItemHandler',
            'textDocument/didChange'                  => 'didChangeJsonRpcQueueItemHandler',
            'textDocument/didSave'                    => 'didSaveJsonRpcQueueItemHandler',
            'textDocument/completion'                 => 'completionJsonRpcQueueItemHandler',
            'textDocument/hover'                      => 'hoverJsonRpcQueueItemHandler',
            'textDocument/definition'                 => 'definitionJsonRpcQueueItemHandler',
            'textDocument/signatureHelp'              => 'signatureHelpJsonRpcQueueItemHandler',
            'textDocument/documentSymbol'             => 'documentSymbolJsonRpcQueueItemHandler',
            'serenata/internal/echoMessage'           => 'echoMessageJsonRpcQueueItemHandler',
            'serenata/internal/index'                 => 'indexJsonRpcQueueItemHandler',
            'serenata/internal/diagnostics'           => 'diagnosticsJsonRpcQueueItemHandler',
            'serenata/deprecated/getClassInfo'        => 'classInfoJsonRpcQueueItemHandler',
            'serenata/deprecated/getClassListForFile' => 'classListJsonRpcQueueItemHandler',
            'serenata/deprecated/deduceTypes'         => 'deduceTypesJsonRpcQueueItemHandler',
            'serenata/deprecated/getGlobalConstants'  => 'globalConstantsJsonRpcQueueItemHandler',
            'serenata/deprecated/getGlobalFunctions'  => 'globalFunctionsJsonRpcQueueItemHandler',
            'serenata/deprecated/resolveType'         => 'resolveTypeJsonRpcQueueItemHandler',
            'serenata/deprecated/localizeType'        => 'localizeTypeJsonRpcQueueItemHandler',
            '$/cancelRequest'                         => 'cancelRequestJsonRpcQueueItemHandler',
        ];

        if (!isset($methodServiceNameMap[$method])) {
            throw new DomainException('Don\'t know any request handler for method "' . $method . '"');
        }

        try {
            return $this->container->get($methodServiceNameMap[$method]);
        } catch (NotFoundExceptionInterface $e) {
            throw new LogicException('Missing service for handling request "' . $method . '"');
        }
    }
}
