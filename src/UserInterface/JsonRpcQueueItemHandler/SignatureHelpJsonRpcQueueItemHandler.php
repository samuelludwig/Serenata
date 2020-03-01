<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Common\Position;

use Serenata\Indexing\TextDocumentContentRegistry;
use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\NameQualificationUtilities\PositionOutOfBoundsPositionalNamespaceDeterminerException;

use Serenata\SignatureHelp\SignatureHelp;
use Serenata\SignatureHelp\SignatureHelpRetriever;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\MessageType;
use Serenata\Utility\MessageLogger;
use Serenata\Utility\LogMessageParams;
use Serenata\Utility\TextDocumentItem;

/**
 * Allows fetching signature help (call tips) for a method or function call.
 */
final class SignatureHelpJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @var SignatureHelpRetriever
     */
    private $signatureHelpRetriever;

    /**
     * @var MessageLogger
     */
    private $messageLogger;

    /**
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     * @param SignatureHelpRetriever      $signatureHelpRetriever
     * @param MessageLogger               $messageLogger
     */
    public function __construct(
        TextDocumentContentRegistry $textDocumentContentRegistry,
        SignatureHelpRetriever $signatureHelpRetriever,
        MessageLogger $messageLogger
    ) {
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
        $this->signatureHelpRetriever = $signatureHelpRetriever;
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
            $result = $this->signatureHelp(
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
     * @return SignatureHelp|null
     */
    public function signatureHelp(string $uri, string $code, Position $position): ?SignatureHelp
    {
        return $this->signatureHelpRetriever->get(new TextDocumentItem($uri, $code), $position);
    }
}
