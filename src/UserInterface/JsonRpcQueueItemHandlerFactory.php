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
    public function create(string $method): Command\CommandInterface
    {
        $methodServiceNameMap = [
            'initialize'                              => 'initializeCommand',
            'initialized'                             => 'initializedCommand',
            'exit'                                    => 'exitCommand',
            'workspace/didChangeWatchedFiles'         => 'didChangeWatchedFilesCommand',
            'textDocument/didChange'                  => 'didChangeCommand',
            'textDocument/didSave'                    => 'didSaveCommand',
            'textDocument/completion'                 => 'completionCommand',
            'textDocument/hover'                      => 'hoverCommand',
            'textDocument/definition'                 => 'definitionCommand',
            'textDocument/signatureHelp'              => 'signatureHelpCommand',
            'textDocument/documentSymbol'             => 'documentSymbolCommand',
            'serenata/internal/echoMessage'           => 'echoMessageCommand',
            'serenata/internal/index'                 => 'indexCommand',
            'serenata/internal/diagnostics'           => 'diagnosticsCommand',
            'serenata/deprecated/getClassInfo'        => 'classInfoCommand',
            'serenata/deprecated/getClassListForFile' => 'classListCommand',
            'serenata/deprecated/deduceTypes'         => 'deduceTypesCommand',
            'serenata/deprecated/getGlobalConstants'  => 'globalConstantsCommand',
            'serenata/deprecated/getGlobalFunctions'  => 'globalFunctionsCommand',
            'serenata/deprecated/resolveType'         => 'resolveTypeCommand',
            'serenata/deprecated/localizeType'        => 'localizeTypeCommand',
            '$/cancelRequest'                         => 'cancelRequestCommand',
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
