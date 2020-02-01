<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Autocompletion\CompletionList;
use Serenata\Autocompletion\AutocompletionPrefixDeterminerInterface;

use Serenata\Autocompletion\Providers\AutocompletionProviderContext;
use Serenata\Autocompletion\Providers\AutocompletionProviderInterface;

use Serenata\Common\Position;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\TextDocumentItem;

/**
 * JsonRpcQueueItemHandlerthat shows autocompletion suggestions at a specific location.
 */
final class CompletionJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var AutocompletionProviderInterface
     */
    private $autocompletionProvider;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @param AutocompletionProviderInterface         $autocompletionProvider
     * @param TextDocumentContentRegistry             $textDocumentContentRegistry
     * @param AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
     */
    public function __construct(
        AutocompletionProviderInterface $autocompletionProvider,
        TextDocumentContentRegistry $textDocumentContentRegistry,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
    ) {
        $this->autocompletionProvider = $autocompletionProvider;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $parameters = $queueItem->getRequest()->getParams() !== null ?
            $queueItem->getRequest()->getParams() :
            [];

        $response = new JsonRpcResponse($queueItem->getRequest()->getId(), new CompletionList(
            true,
            $this->getSuggestions(
                $parameters['textDocument']['uri'],
                $this->textDocumentContentRegistry->get($parameters['textDocument']['uri']),
                new Position($parameters['position']['line'], $parameters['position']['character'])
            )
        ));

        $deferred = new Deferred();
        $deferred->resolve($response);

        return $deferred->promise();
    }

    /**
     * @param string   $uri
     * @param string   $code
     * @param Position $position
     *
     * @return array<string,mixed>
     */
    public function getSuggestions(string $uri, string $code, Position $position): array
    {
        $result = $this->autocompletionProvider->provide(new AutocompletionProviderContext(
            new TextDocumentItem($uri, $code),
            $position,
            $this->autocompletionPrefixDeterminer->determine($code, $position)
        ));

        return is_array($result) ? $result : iterator_to_array($result);
    }
}
