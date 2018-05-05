<?php

namespace Serenata\UserInterface\Command;

use Serenata\Indexing\StorageVersionChecker;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that tests a project to see if it is in a properly usable state.
 */
final class TestCommand extends AbstractCommand
{
    /**
     * @var StorageVersionChecker
     */
    private $storageVersionChecker;

    /**
     * @param StorageVersionChecker  $storageVersionChecker
     */
    public function __construct(StorageVersionChecker $storageVersionChecker)
    {
        $this->storageVersionChecker = $storageVersionChecker;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        return new JsonRpcResponse($queueItem->getRequest()->getId(), $this->test());
    }

    /**
     * @return bool
     */
    public function test(): bool
    {
        return $this->storageVersionChecker->isUpToDate();
    }
}
