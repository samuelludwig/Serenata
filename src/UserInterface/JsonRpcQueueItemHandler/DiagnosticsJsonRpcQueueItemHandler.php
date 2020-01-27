<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Linting\Linter;
use Serenata\Linting\PublishDiagnosticsParams;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Handles diagnostics requests.
 *
 * This command should not be invoked from outside the server. It is purely destined for internal use to be able to
 * invoke it in a delayed maner from inside other parts of the code base.
 */
final class DiagnosticsJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var Linter
     */
    private $linter;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @param Linter                      $linter
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     */
    public function __construct(Linter $linter, TextDocumentContentRegistry $textDocumentContentRegistry)
    {
        $this->linter = $linter;
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

        if (!isset($parameters['uri'])) {
            throw new InvalidArgumentsException('"uri" parameter must be supplied');
        }

        // Don't send a response, but send a notification (request with null ID) instead.
        $response = new JsonRpcRequest(
            null,
            'textDocument/publishDiagnostics',
            $this->lint(
                $parameters['uri'],
                $this->textDocumentContentRegistry->get($parameters['uri'])
            )->jsonSerialize()
        );

        $deferred = new Deferred();
        $deferred->resolve($response);

        return $deferred->promise();
    }

    /**
     * @param string $uri
     * @param string $code
     *
     * @return PublishDiagnosticsParams
     */
    public function lint(string $uri, string $code): PublishDiagnosticsParams
    {
        return new PublishDiagnosticsParams($uri, $this->linter->lint($code));
    }
}
