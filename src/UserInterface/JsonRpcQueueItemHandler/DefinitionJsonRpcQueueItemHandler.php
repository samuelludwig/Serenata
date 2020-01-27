<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Common\Position;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\GotoDefinition\DefinitionLocator;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\Location;
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
     * @param DefinitionLocator           $definitionLocator
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     */
    public function __construct(
        DefinitionLocator $definitionLocator,
        TextDocumentContentRegistry $textDocumentContentRegistry
    ) {
        $this->definitionLocator = $definitionLocator;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams() !== null ?
            $queueItem->getRequest()->getParams() :
            [];

        $response = new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->gotoDefinition(
                $parameters['textDocument']['uri'],
                $this->textDocumentContentRegistry->get($parameters['textDocument']['uri']),
                new Position($parameters['position']['line'], $parameters['position']['character'])
            )
        );

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
