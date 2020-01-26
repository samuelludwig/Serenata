<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Analysis\ConstantListProviderInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * JsonRpcQueueItemHandlerthat shows a list of global constants.
 *
 * @deprecated Will be removed as soon as all functionality this facilitates is implemented as LSP-compliant requests.
 */
final class GlobalConstantsJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var ConstantListProviderInterface
     */
    private $constantListProvider;

    /**
     * @param ConstantListProviderInterface $constantListProvider
     */
    public function __construct(ConstantListProviderInterface $constantListProvider)
    {
        $this->constantListProvider = $constantListProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $deferred = new Deferred();
        $deferred->resolve(new JsonRpcResponse($queueItem->getRequest()->getId(), $this->getGlobalConstants()));

        return $deferred->promise();
    }

    /**
     * @return array
     */
    public function getGlobalConstants(): array
    {
        return $this->constantListProvider->getAll();
    }
}
