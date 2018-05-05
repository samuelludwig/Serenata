<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\ConstantListProviderInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that shows a list of global constants.
 */
final class GlobalConstantsCommand extends AbstractCommand
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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
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
