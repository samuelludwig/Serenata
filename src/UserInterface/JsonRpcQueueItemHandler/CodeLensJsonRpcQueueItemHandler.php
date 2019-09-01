<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\CodeLenses\CodeLens;
use Serenata\CodeLenses\CodeLensesRetriever;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

use Serenata\Utility\TextDocumentItem;

/**
 * Handler that retrieves a list of code lenses for a document.
 */
final class CodeLensJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var CodeLensesRetriever
     */
    private $codeLensesRetriever;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @param CodeLensesRetriever         $codeLensesRetriever
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     */
    public function __construct(
        CodeLensesRetriever $codeLensesRetriever,
        TextDocumentContentRegistry $textDocumentContentRegistry
    ) {
        $this->codeLensesRetriever = $codeLensesRetriever;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $parameters = $queueItem->getRequest()->getParams() ?: [];

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->getAll(
                $parameters['textDocument']['uri'],
                $this->textDocumentContentRegistry->get($parameters['textDocument']['uri'])
            )
        );
    }

    /**
     * @param string $uri
     * @param string $code
     *
     * @return CodeLens[]|null
     */
    public function getAll(string $uri, string $code): ?array
    {
        return $this->codeLensesRetriever->retrieve(new TextDocumentItem($uri, $code));
    }
}
