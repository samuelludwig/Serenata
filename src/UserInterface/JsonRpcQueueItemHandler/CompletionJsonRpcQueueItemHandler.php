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
use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\NameQualificationUtilities\PositionOutOfBoundsPositionalNamespaceDeterminerException;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\MessageType;
use Serenata\Utility\MessageLogger;
use Serenata\Utility\LogMessageParams;
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
     * @var MessageLogger
     */
    private $messageLogger;

    /**
     * @param AutocompletionProviderInterface         $autocompletionProvider
     * @param TextDocumentContentRegistry             $textDocumentContentRegistry
     * @param AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
     * @param MessageLogger                           $messageLogger
     */
    public function __construct(
        AutocompletionProviderInterface $autocompletionProvider,
        TextDocumentContentRegistry $textDocumentContentRegistry,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer,
        MessageLogger $messageLogger
    ) {
        $this->autocompletionProvider = $autocompletionProvider;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
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
            $results = $this->getSuggestions(
                $parameters['textDocument']['uri'],
                $this->textDocumentContentRegistry->get($parameters['textDocument']['uri']),
                new Position($parameters['position']['line'], $parameters['position']['character'])
            );
        } catch (FileNotFoundStorageException|PositionOutOfBoundsPositionalNamespaceDeterminerException $e) {
            $this->messageLogger->log(
                new LogMessageParams(MessageType::WARNING, $e->getMessage()),
                $queueItem->getJsonRpcMessageSender()
            );

            $results = [];
        }

        $response = new JsonRpcResponse($queueItem->getRequest()->getId(), new CompletionList(true, $results));

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
