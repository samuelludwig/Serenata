<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        return new JsonRpcResponse($queueItem->getRequest()->getId(), $this->getGlobalFunctions());
    }

     /**
      * @return array
      */
    public function getGlobalFunctions(): array
    {
        return $this->functionListProvider->getAll();
    }
}
