<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * JsonRpcQueueItemHandlerthat shows a list of global functions.
 *
 * @deprecated Will be removed as soon as all functionality this facilitates is implemented as LSP-compliant requests.
 */
final class GlobalFunctionsJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @param FunctionListProviderInterface $functionListProvider
     */
    public function __construct(FunctionListProviderInterface $functionListProvider)
    {
        $this->functionListProvider = $functionListProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $deferred = new Deferred();
        $deferred->resolve(new JsonRpcResponse($queueItem->getRequest()->getId(), $this->getGlobalFunctions()));

        return $deferred->promise();
    }

     /**
      * @return array<string,mixed>
      */
    public function getGlobalFunctions(): array
    {
        return $this->functionListProvider->getAll();
    }
}
