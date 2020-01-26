<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Analysis\ClearableCacheInterface;

use Serenata\Indexing\ManagerRegistry;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * Handles the shutdown request.
 */
final class ShutdownJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @var ClearableCacheInterface
     */
    private $cache;

    /**
     * @param ManagerRegistry         $managerRegistry
     * @param ActiveWorkspaceManager  $activeWorkspaceManager
     * @param ClearableCacheInterface $cache
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ActiveWorkspaceManager $activeWorkspaceManager,
        ClearableCacheInterface $cache
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->activeWorkspaceManager = $activeWorkspaceManager;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $this->shutdown();

        $deferred = new Deferred();
        $deferred->resolve(new JsonRpcResponse($queueItem->getRequest()->getId(), null));

        return $deferred->promise();
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
        $this->managerRegistry->ensureConnectionClosed();

        $this->activeWorkspaceManager->setActiveWorkspace(null);

        $this->cache->clearCache();
    }
}
