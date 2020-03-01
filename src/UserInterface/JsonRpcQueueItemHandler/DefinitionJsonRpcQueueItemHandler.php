<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Common\Position;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\GotoDefinition\DefinitionLocator;

use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\NameQualificationUtilities\PositionOutOfBoundsPositionalNamespaceDeterminerException;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\Location;
use Serenata\Utility\MessageType;
use Serenata\Utility\MessageLogger;
use Serenata\Utility\LogMessageParams;
use Serenata\Utility\TextDocumentItem;

/**
 * Allows navigating to the definition of a structural element by returning the location of its definition.
 */
final class DefinitionJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var DefinitionLocator
     */
    private $definitionLocator;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @var MessageLogger
     */
    private $messageLogger;

    /**
     * @param DefinitionLocator           $definitionLocator
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     * @param MessageLogger               $messageLogger
     */
    public function __construct(
        DefinitionLocator $definitionLocator,
        TextDocumentContentRegistry $textDocumentContentRegistry,
        MessageLogger $messageLogger
    ) {
        $this->definitionLocator = $definitionLocator;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
        $this->messageLogger = $messageLogger;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams() !== null ?
            $queueItem->getRequest()->getParams() :
            [];

        try {
            $result = $this->gotoDefinition(
                $parameters['textDocument']['uri'],
                $this->textDocumentContentRegistry->get($parameters['textDocument']['uri']),
                new Position($parameters['position']['line'], $parameters['position']['character'])
            );
        } catch (FileNotFoundStorageException|PositionOutOfBoundsPositionalNamespaceDeterminerException $e) {
            $this->messageLogger->log(
                new LogMessageParams(MessageType::WARNING, $e->getMessage()),
                $queueItem->getJsonRpcMessageSender()
            );

            $result = null;
        }

        $response = new JsonRpcResponse($queueItem->getRequest()->getId(), $result);

        $deferred = new Deferred();
        $deferred->resolve($response);

        return $deferred->promise();
    }

    /**
     * @param string   $uri
     * @param string   $code
     * @param Position $position
     *
     * @return Location|Location[]|null
     */
    public function gotoDefinition(string $uri, string $code, Position $position)
    {
        return $this->definitionLocator->locate(new TextDocumentItem($uri, $code), $position)->getResult();
    }
}
