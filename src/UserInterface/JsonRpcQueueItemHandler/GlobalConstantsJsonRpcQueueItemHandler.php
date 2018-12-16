<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Analysis\ConstantListProviderInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        return new JsonRpcResponse($queueItem->getRequest()->getId(), $this->getGlobalConstants());
    }

    /**
     * @return array
     */
    public function getGlobalConstants(): array
    {
        return $this->constantListProvider->getAll();
    }
}
