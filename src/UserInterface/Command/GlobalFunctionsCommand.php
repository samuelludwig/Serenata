<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\FunctionListProviderInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that shows a list of global functions.
 */
final class GlobalFunctionsCommand extends AbstractCommand
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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
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
