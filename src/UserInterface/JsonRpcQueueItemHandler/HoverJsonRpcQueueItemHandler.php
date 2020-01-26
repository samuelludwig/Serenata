<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Common\Position;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Tooltips\TooltipResult;
use Serenata\Tooltips\TooltipProvider;

use Serenata\Utility\TextDocumentItem;

/**
 * JsonRpcQueueItemHandlerthat fetches tooltip information for a specific location.
 */
final class HoverJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @var TooltipProvider
     */
    private $tooltipProvider;

    /**
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     * @param TooltipProvider             $tooltipProvider
     */
    public function __construct(
        TextDocumentContentRegistry $textDocumentContentRegistry,
        TooltipProvider $tooltipProvider
    ) {
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
        $this->tooltipProvider = $tooltipProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams() ?: [];

        $response = new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->getTooltip(
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
     * @return TooltipResult|null
     */
    public function getTooltip(string $uri, string $code, Position $position): ?TooltipResult
    {
        return $this->tooltipProvider->get(new TextDocumentItem($uri, $code), $position);
    }
}
